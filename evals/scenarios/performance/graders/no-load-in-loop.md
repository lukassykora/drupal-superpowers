---
type: "file_contains"
path: "web/modules/custom/partner_directory/src/Controller/PartnerListController.php"
pattern: "foreach[^}]*->load\\(\\$id\\)"
match: "not_contains"
---
