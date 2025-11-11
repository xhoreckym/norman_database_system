-- Migration SQL queries to populate organisation_id and country_id in users table
-- Run these queries before dropping the text columns

-- Query 1: Update users.organisation_id based on list_data_source_organisations.acronym = users.organisation
UPDATE users
SET organisation_id = list_data_source_organisations.id
FROM list_data_source_organisations
WHERE users.organisation IS NOT NULL
  AND users.organisation != ''
  AND TRIM(users.organisation) = TRIM(list_data_source_organisations.acronym);

-- Query 2: Update users.country_id based on list_countries.code = users.country
UPDATE users
SET country_id = list_countries.id
FROM list_countries
WHERE users.country IS NOT NULL
  AND users.country != ''
  AND TRIM(UPPER(users.country)) = TRIM(UPPER(list_countries.code));

-- Optional: Check how many users were matched
-- SELECT
--   COUNT(*) as total_users,
--   COUNT(organisation_id) as matched_organisations,
--   COUNT(country_id) as matched_countries
-- FROM users;

-- Optional: Check unmatched users
-- SELECT id, email, organisation, country
-- FROM users
-- WHERE (organisation IS NOT NULL AND organisation != '' AND organisation_id IS NULL)
--    OR (country IS NOT NULL AND country != '' AND country_id IS NULL);
