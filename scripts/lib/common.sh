#!/usr/bin/env bash
# Shared helpers for drupal-superpowers scripts. Source this file; do not execute it.
# Zero dependencies beyond bash 3.2+, coreutils, grep, sed, awk. JSON parsing uses the first
# available of php or python3 (see dsp_json); a grep fallback covers the Drupal core version only.

DSP_ROOT="${DSP_ROOT:-$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)}"

dsp_have() { command -v "$1" >/dev/null 2>&1; }

# Escape a string as a JSON string literal (with quotes).
dsp_jstr() {
  local s=${1-}
  s=${s//\\/\\\\}
  s=${s//\"/\\\"}
  s=${s//$'\n'/\\n}
  s=${s//$'\r'/\\r}
  s=${s//$'\t'/\\t}
  printf '"%s"' "$s"
}

# JSON value or null.
dsp_jval() { if [ -z "${1-}" ]; then printf 'null'; else dsp_jstr "$1"; fi; }

# JSON array of strings from newline-separated input on stdin.
dsp_jarr() {
  local first=1 line
  printf '['
  while IFS= read -r line; do
    [ -z "$line" ] && continue
    [ $first -eq 1 ] || printf ','
    first=0
    dsp_jstr "$line"
  done
  printf ']'
}

# dsp_json <op> <args...>
#   get <file> <dotted.path>       -> scalar (raw) or JSON for objects/arrays; empty if missing
#   keys <file> <dotted.path>      -> one key per line
#   lockversions <file>            -> "name version" per line for packages and packages-dev
#   quote <string>                 -> JSON string literal
dsp_json() {
  if dsp_have php; then
    php -r '
      $op=$argv[1]; $a=array_slice($argv,2);
      $walk=function($d,$p){ if($p==="") return $d; foreach(explode(".",$p) as $k){ if(is_array($d)&&array_key_exists($k,$d)) $d=$d[$k]; else return null; } return $d; };
      if($op==="quote"){ echo json_encode($a[0], JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE); exit; }
      $raw=@file_get_contents($a[0]); $d=$raw===false?null:json_decode($raw,true);
      if($d===null) exit(0);
      if($op==="get"){ $v=$walk($d,$a[1]); if($v===null) exit(0); echo is_scalar($v)?(is_bool($v)?($v?"true":"false"):$v):json_encode($v, JSON_UNESCAPED_SLASHES); exit; }
      if($op==="keys"){ $v=$walk($d,$a[1]); if(is_array($v)) foreach(array_keys($v) as $k) echo $k,"\n"; exit; }
      if($op==="lockversions"){ foreach(["packages","packages-dev"] as $s) if(!empty($d[$s])) foreach($d[$s] as $p) echo $p["name"]," ",$p["version"],"\n"; exit; }
    ' -- "$@"
  elif dsp_have python3; then
    python3 - "$@" <<'PY'
import json, sys
op, a = sys.argv[1], sys.argv[2:]
def walk(d, p):
    if p == "": return d
    for k in p.split("."):
        if isinstance(d, dict) and k in d: d = d[k]
        elif isinstance(d, list) and k.isdigit() and int(k) < len(d): d = d[int(k)]
        else: return None
    return d
if op == "quote":
    print(json.dumps(a[0], ensure_ascii=False)); sys.exit()
try:
    d = json.load(open(a[0]))
except Exception:
    sys.exit(0)
if op == "get":
    v = walk(d, a[1])
    if v is None: sys.exit(0)
    print(v if isinstance(v, str) else (str(v).lower() if isinstance(v, bool) else (v if isinstance(v, (int, float)) else json.dumps(v))))
elif op == "keys":
    v = walk(d, a[1])
    if isinstance(v, dict):
        for k in v: print(k)
elif op == "lockversions":
    for s in ("packages", "packages-dev"):
        for p in d.get(s) or []:
            print(p.get("name"), p.get("version"))
PY
  else
    # Fallback: no JSON engine. Only quote and lockversions (grep-based) are supported.
    case "$1" in
      quote) dsp_jstr "$2" ;;
      lockversions) grep -Eo '"name": *"[^"]+"|"version": *"[^"]+"' "$2" 2>/dev/null | sed -E 's/"(name|version)": *"([^"]+)"/\2/' | paste - - 2>/dev/null ;;
      *) return 0 ;;
    esac
  fi
}

# Walk up from $1 (default cwd) to find a Drupal Composer root or a Drupal 7 root.
# Prints "<path> <kind>" where kind is composer|d7|none.
dsp_find_root() {
  local dir; dir=$(cd "${1:-.}" 2>/dev/null && pwd) || { echo ". none"; return; }
  while :; do
    if [ -f "$dir/composer.lock" ] && grep -q '"name": *"drupal/core"' "$dir/composer.lock" 2>/dev/null; then echo "$dir composer"; return; fi
    if [ -f "$dir/composer.json" ] && grep -Eq '"drupal/(core|core-recommended|core-composer-scaffold)"' "$dir/composer.json" 2>/dev/null; then echo "$dir composer"; return; fi
    if [ -f "$dir/composer.json" ] && grep -Eq '"type": *"drupal-(module|theme|profile|custom-module)"' "$dir/composer.json" 2>/dev/null; then echo "$dir composer"; return; fi
    if [ -f "$dir/includes/bootstrap.inc" ] && grep -q "DRUPAL_CORE_COMPATIBILITY', '7.x'" "$dir/includes/bootstrap.inc" 2>/dev/null; then echo "$dir d7"; return; fi
    if [ -f "$dir/core/lib/Drupal.php" ]; then echo "$dir core"; return; fi
    [ "$dir" = "/" ] && break
    dir=$(dirname "$dir")
  done
  echo "$(pwd) none"
}

# Docroot relative to composer root, or "." when not found.
dsp_find_docroot() {
  local root=$1 wr
  if [ -f "$root/composer.json" ]; then
    wr=$(dsp_json get "$root/composer.json" "extra.drupal-scaffold.locations.web-root")
    wr=${wr%/}
    [ -n "$wr" ] && [ -d "$root/$wr" ] && { echo "$wr"; return; }
  fi
  for c in web docroot html public .; do
    if [ -f "$root/$c/core/lib/Drupal.php" ] || [ -d "$root/$c/sites/default" ] || [ -f "$root/$c/index.php" ]; then echo "$c"; return; fi
  done
  echo "."
}

# Version compare: dsp_vergte A B -> 0 if A >= B (numeric dotted, pre-release suffixes ignored).
dsp_vergte() {
  local a=${1%%-*} b=${2%%-*}
  [ "$(printf '%s\n%s\n' "$b" "$a" | sort -V | head -1)" = "$b" ]
}

# Extract the fenced ```json block named by a "<!-- json:<name> -->" marker line from a markdown file.
dsp_md_json_block() {
  local file=$1 name=$2
  awk -v want="<!-- json:${name} -->" '
    $0==want {armed=1; next}
    armed && /^```json/ {inblk=1; next}
    inblk && /^```/ {exit}
    inblk {print}
  ' "$file"
}

# Data directory for caches.
dsp_data_dir() {
  local d="${CLAUDE_PLUGIN_DATA:-${TMPDIR:-/tmp}/drupal-superpowers}"
  mkdir -p "$d" 2>/dev/null
  echo "$d"
}
