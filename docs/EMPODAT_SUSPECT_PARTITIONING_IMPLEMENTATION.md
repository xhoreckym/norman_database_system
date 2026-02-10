# Empodat Suspect Main Table Partitioning Implementation Guide

## Overview

This guide describes the implementation of PostgreSQL table partitioning for `empodat_suspect_main` to separate numeric concentration records (~4M) from non-numeric concentration records (~30M).

### Rationale

- **Current state:** Only numeric concentrations are stored, non-numeric values (NA, NULL, text) are skipped during seeding
- **Goal:** Store all records, but partition them for efficient searching
- **Benefit:** Searches only scan the numeric partition (4M rows), while archive data (30M rows) is physically separated

### Partition Structure

```
empodat_suspect_main (partitioned by LIST on is_numeric_concentration)
├── empodat_suspect_main_numeric      (is_numeric_concentration = TRUE)  ~4M rows
└── empodat_suspect_main_nonnumeric   (is_numeric_concentration = FALSE) ~30M rows
```

---

## Pre-Implementation Checklist

- [ ] Database backup completed
- [ ] No incoming foreign keys to `empodat_suspect_main` (verified: empty set)
- [ ] All seeders identified (7 total)
- [ ] Excel source files available for re-seeding

---

## Step 1: Create Migration

**File:** `database/migrations/YYYY_MM_DD_HHMMSS_recreate_empodat_suspect_main_as_partitioned.php`

**Command:** `php artisan make:migration recreate_empodat_suspect_main_as_partitioned`

### Migration `up()` Requirements

1. **Drop existing table:**
   ```sql
   DROP TABLE IF EXISTS empodat_suspect_main;
   ```

2. **Create partitioned table:**
   ```sql
   CREATE TABLE empodat_suspect_main (
       id BIGSERIAL,
       is_numeric_concentration BOOLEAN NOT NULL,
       file_id BIGINT NULL,
       substance_id BIGINT NULL,
       xlsx_station_mapping_id BIGINT NULL,
       station_id BIGINT NULL,
       concentration DOUBLE PRECISION NULL,
       ip TEXT NULL,
       ip_max DOUBLE PRECISION NULL,
       based_on_hrms_library BOOLEAN NULL,
       units VARCHAR(255) NULL,
       PRIMARY KEY (id, is_numeric_concentration)
   ) PARTITION BY LIST (is_numeric_concentration);
   ```

3. **Create partitions:**
   ```sql
   CREATE TABLE empodat_suspect_main_numeric
       PARTITION OF empodat_suspect_main
       FOR VALUES IN (TRUE);

   CREATE TABLE empodat_suspect_main_nonnumeric
       PARTITION OF empodat_suspect_main
       FOR VALUES IN (FALSE);
   ```

4. **Create indexes on numeric partition:**
   ```sql
   CREATE INDEX idx_esm_numeric_station_id ON empodat_suspect_main_numeric (station_id);
   CREATE INDEX idx_esm_numeric_substance_id ON empodat_suspect_main_numeric (substance_id);
   CREATE INDEX idx_esm_numeric_ip_max ON empodat_suspect_main_numeric (ip_max);
   CREATE INDEX idx_esm_numeric_file_id ON empodat_suspect_main_numeric (file_id);
   ```

5. **Create indexes on non-numeric partition (minimal):**
   ```sql
   CREATE INDEX idx_esm_nonnumeric_station_id ON empodat_suspect_main_nonnumeric (station_id);
   CREATE INDEX idx_esm_nonnumeric_substance_id ON empodat_suspect_main_nonnumeric (substance_id);
   ```

6. **Add foreign key constraints (on parent table):**
   ```sql
   ALTER TABLE empodat_suspect_main
       ADD CONSTRAINT fk_esm_substance
       FOREIGN KEY (substance_id) REFERENCES susdat_substances(id);

   ALTER TABLE empodat_suspect_main
       ADD CONSTRAINT fk_esm_station
       FOREIGN KEY (station_id) REFERENCES empodat_stations(id);

   ALTER TABLE empodat_suspect_main
       ADD CONSTRAINT fk_esm_xlsx_mapping
       FOREIGN KEY (xlsx_station_mapping_id) REFERENCES empodat_suspect_xlsx_stations_mapping(id);

   ALTER TABLE empodat_suspect_main
       ADD CONSTRAINT fk_esm_file
       FOREIGN KEY (file_id) REFERENCES files(id);
   ```

### Migration `down()` Requirements

1. Drop partitioned table (cascades to partitions)
2. Recreate original non-partitioned table structure (copy from original migration)

**Note:** Use `DB::statement()` for all SQL - Laravel Schema builder doesn't support PostgreSQL partitioning.

---

## Step 2: Update Model

**File:** `app/Models/EmpodatSuspect/EmpodatSuspectMain.php`

### Changes Required

1. **Add to `$fillable` array:**
   ```php
   'is_numeric_concentration',
   ```

2. **Add to `$casts` array:**
   ```php
   'is_numeric_concentration' => 'boolean',
   ```

3. **Add new scope methods:**
   ```php
   public function scopeNumericOnly($query)
   {
       return $query->where('is_numeric_concentration', true);
   }

   public function scopeNonNumericOnly($query)
   {
       return $query->where('is_numeric_concentration', false);
   }
   ```

---

## Step 3: Update Seeders

### Files to Modify (7 seeders)

1. `database/seeders/EmpodatSuspect/EmpodatSuspectBiotaMainSeeder.php`
2. `database/seeders/EmpodatSuspect/EmpodatSuspectSedimentMainSeeder.php`
3. `database/seeders/EmpodatSuspect/EmpodatSuspectApexMainSeeder.php`
4. `database/seeders/EmpodatSuspect/EmpodatSuspectConnect2BiotaMainSeeder.php`
5. `database/seeders/EmpodatSuspect/EmpodatSuspectConnect2SedimentsMainSeeder.php`
6. `database/seeders/EmpodatSuspect/EmpodatSuspectHelcomBiotaMainSeeder.php`
7. `database/seeders/EmpodatSuspect/EmpodatSuspectHelcomSedimentsMainSeeder.php`

### Changes in `processRow()` Method

**Current logic (REMOVE):**
```php
// Skip NA or empty values
if ($this->isNullOrNA($concentrationValue)) {
    continue;
}

// Clean the concentration value
$concentration = $this->cleanDouble($concentrationValue);
if ($concentration === null) {
    continue;
}
```

**New logic (REPLACE WITH):**
```php
// Determine if concentration is numeric
$concentration = $this->cleanDouble($concentrationValue);
$isNumeric = ($concentration !== null);

// Create record for ALL values (don't skip non-numeric)
$records[] = [
    'file_id' => $this->fileId,
    'substance_id' => $substanceId,
    'xlsx_station_mapping_id' => $mappingData['mapping_id'],
    'station_id' => $mappingData['station_id'],
    'concentration' => $concentration,  // NULL for non-numeric
    'is_numeric_concentration' => $isNumeric,  // NEW FIELD
    'ip' => $ip,
    'ip_max' => $ipMax,
    'based_on_hrms_library' => $basedOnHRMSLibrary,
    'units' => $units,
];
```

### Key Changes Summary

| Before | After |
|--------|-------|
| Skip non-numeric rows | Create record for ALL rows |
| No `is_numeric_concentration` field | Add `is_numeric_concentration` field |
| `concentration` always has value | `concentration` can be NULL |

---

## Step 4: Update Controller

**File:** `app/Http/Controllers/EmpodatSuspect/EmpodatSuspectController.php`

### Changes in `search()` Method

**Location:** After initial query builder setup (~line 264)

**Add this line:**
```php
$empodatSuspects = EmpodatSuspectMain::query()
    ->select('empodat_suspect_main.*')
    ->where('empodat_suspect_main.is_numeric_concentration', true)  // ADD THIS LINE
    ->whereNotNull('empodat_suspect_main.station_id')
    ->whereNotNull('empodat_suspect_main.substance_id')
    // ... rest of query
```

### Changes in `getQueryRecordCount()` Method

**Location:** Around line 845

**Add this line:**
```php
$query = EmpodatSuspectMain::query()
    ->where('is_numeric_concentration', true)  // ADD THIS LINE
    ->whereNotNull('station_id')
    ->whereNotNull('substance_id');
```

---

## Step 5: Run Migration

```bash
php artisan migrate
```

**Expected result:** Empty partitioned table created

**Verification:**
```sql
SELECT
    tableoid::regclass AS partition_name,
    COUNT(*) AS row_count
FROM empodat_suspect_main
GROUP BY tableoid;
```

Should return 0 rows (table is empty).

---

## Step 6: Run Seeders

Execute each seeder in order:

```bash
php artisan db:seed --class=Database\\Seeders\\EmpodatSuspect\\EmpodatSuspectBiotaMainSeeder
php artisan db:seed --class=Database\\Seeders\\EmpodatSuspect\\EmpodatSuspectSedimentMainSeeder
php artisan db:seed --class=Database\\Seeders\\EmpodatSuspect\\EmpodatSuspectApexMainSeeder
php artisan db:seed --class=Database\\Seeders\\EmpodatSuspect\\EmpodatSuspectConnect2BiotaMainSeeder
php artisan db:seed --class=Database\\Seeders\\EmpodatSuspect\\EmpodatSuspectConnect2SedimentsMainSeeder
php artisan db:seed --class=Database\\Seeders\\EmpodatSuspect\\EmpodatSuspectHelcomBiotaMainSeeder
php artisan db:seed --class=Database\\Seeders\\EmpodatSuspect\\EmpodatSuspectHelcomSedimentsMainSeeder
```

**Monitor progress after each seeder:**
```sql
SELECT
    tableoid::regclass AS partition_name,
    COUNT(*) AS row_count,
    pg_size_pretty(pg_total_relation_size(tableoid::regclass)) AS size
FROM empodat_suspect_main
GROUP BY tableoid;
```

---

## Step 7: Refresh Materialized Views

```bash
php artisan db:seed --class=Database\\Seeders\\EmpodatSuspect\\CreateEmpodatSuspectMaterializedViewSeeder
```

---

## Step 8: Verification

### 8.1 Check Partition Row Counts

```sql
SELECT
    tableoid::regclass AS partition_name,
    COUNT(*) AS row_count,
    pg_size_pretty(pg_total_relation_size(tableoid::regclass)) AS size
FROM empodat_suspect_main
GROUP BY tableoid;
```

**Expected:**
- `empodat_suspect_main_numeric`: ~4M rows
- `empodat_suspect_main_nonnumeric`: ~30M rows

### 8.2 Verify Partition Pruning

```sql
EXPLAIN (ANALYZE, COSTS, VERBOSE, BUFFERS)
SELECT * FROM empodat_suspect_main
WHERE is_numeric_concentration = TRUE
AND station_id = 12345;
```

**Expected:** Output shows only `empodat_suspect_main_numeric` partition is scanned. Look for:
- `Subplans Removed: 1` or
- Only `empodat_suspect_main_numeric` appears in the plan

### 8.3 Test Application

1. Navigate to Empodat Suspect search page
2. Perform search with various filters (country, matrix, substance, year)
3. Verify results are returned correctly
4. Verify pagination works
5. Test CSV export functionality
6. Test record detail page

---

## Rollback Procedure

If issues occur:

1. **Rollback migration:**
   ```bash
   php artisan migrate:rollback
   ```

2. **Re-seed original data:**
   Run original seeders (they will only create numeric records as before)

---

## Files Changed Summary

| File | Action |
|------|--------|
| `database/migrations/YYYY_..._recreate_empodat_suspect_main_as_partitioned.php` | CREATE |
| `app/Models/EmpodatSuspect/EmpodatSuspectMain.php` | MODIFY |
| `app/Http/Controllers/EmpodatSuspect/EmpodatSuspectController.php` | MODIFY |
| `database/seeders/EmpodatSuspect/EmpodatSuspectBiotaMainSeeder.php` | MODIFY |
| `database/seeders/EmpodatSuspect/EmpodatSuspectSedimentMainSeeder.php` | MODIFY |
| `database/seeders/EmpodatSuspect/EmpodatSuspectApexMainSeeder.php` | MODIFY |
| `database/seeders/EmpodatSuspect/EmpodatSuspectConnect2BiotaMainSeeder.php` | MODIFY |
| `database/seeders/EmpodatSuspect/EmpodatSuspectConnect2SedimentsMainSeeder.php` | MODIFY |
| `database/seeders/EmpodatSuspect/EmpodatSuspectHelcomBiotaMainSeeder.php` | MODIFY |
| `database/seeders/EmpodatSuspect/EmpodatSuspectHelcomSedimentsMainSeeder.php` | MODIFY |

---

## Notes

- PostgreSQL 10+ required for declarative partitioning
- Laravel Schema builder doesn't support partitioning - use raw SQL via `DB::statement()`
- Primary key must include partition key `(id, is_numeric_concentration)`
- Foreign keys are defined on parent table, automatically apply to all partitions
- Indexes must be created on each partition separately (or use index on parent which propagates)
