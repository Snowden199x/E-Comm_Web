# Shipping location snapshot

`psgc-locations.json` contains province and city/municipality names and codes fetched on 26 September 2026 from the PSGC API mirror used by the existing registration form: https://psgc.gitlab.io/api/. The authoritative classification is the Philippine Statistics Authority's PSGC: https://psa.gov.ph/classifications-api/psgc. The snapshot adds Metro Manila (`130000000`) as a selectable top-level area because the mirror's provinces list omits NCR. Two independent cities without a province association in the mirror are omitted. This is a fixed operational reference and must be reviewed when PSGC boundaries change.

The server validates buyer checkout city/province combinations against this snapshot. Seller and logistics-center profile names are matched against one another by normalized province/city name; the service does not infer distances or service coverage.
