# Rider Registration API

**Status:** Backend implemented; mobile client added in the separate E-Comm_Mobile repository
**Updated:** 27 September 2026

`GET /api/v1/rider/locations` exposes the bundled PSGC province/city snapshot. After selecting a city, `GET /api/v1/rider/locations/{cityCode}/barangays` returns the bundled barangay code/name choices for that city and returns 404 for an unknown city. `POST /api/v1/rider/register` accepts personal, vehicle, and address fields including `province_code`, `city_code`, and `barangay_code`, plus three image uploads (`valid_id`, `drivers_license`, `or_cr`). These endpoints are public and rate limited. The server validates that the city belongs to the province and that the barangay belongs to the city; it also validates document MIME and size, email uniqueness, password confirmation, and the supplied fields. The client never selects a logistics-center ID.

The server applies the same routing rule used for seller-ready orders: select one approved, active logistics center in the same city; otherwise select one in the same province. If none or more than one qualifies at the applicable level, registration returns a validation error and creates no rider. This is service-area matching by names from the PSGC snapshot, not a physical-distance or capacity calculation.

Successful registration stores verification images on the private local disk and creates a `users` row with `role=courier`, `status=pending`, plus a `courier_details` row tied to the matched center, inside one database transaction. The matched center sees the application in Rider Management and alone can approve/reject it. The API returns the center name and pending status; it does not issue a bearer token. The login endpoint checks rider and center approval before issuing one.

The province/city catalog and grouped barangay snapshot are bundled under `resources/data/`, so rider registration needs no live PSGC mirror request. The source snapshot is fixed and may lag current administrative changes; see `resources/data/README.md`. No new migration is required for this endpoint. Reviewer access to the private documents, automated email verification, reassignment when no unique center exists, and a dedicated approval audit record remain unimplemented. The owner performs acceptance testing; no automated tests or app walkthroughs were run by the agent.
