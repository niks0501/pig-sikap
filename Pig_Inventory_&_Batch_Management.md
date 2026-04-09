# Pig Inventory & Batch Management Module — Implementation Guide for Planning Agent

## Purpose

Implement the **Pig Inventory & Batch Management Module** for the Pig-Sikap system using a **hybrid inventory model**:

- **Batch/Litter is the primary record**
- **Individual pig profiles are optional and lightweight**
- The module must remain **simple, mobile-friendly, and aligned with the client’s actual workflow**

This guide is for a planning/implementation agent so it can break the work into concrete tasks, architecture decisions, database design, backend structure, and UI flow.

---

# 1. Core Problem This Module Solves

The client currently manages pigs manually through notebooks and logbooks. Their real practice is:

- pigs are managed mainly by **litter/anakan**
- one litter is **not mixed** with others
- pigs may also be identified individually using **ear cuts**
- records are updated mainly by the **President, Secretary, and Treasurer**
- the system should **preserve the manual practice**, not replace it with a complex farm ERP

Therefore, the module must:

- treat **batch/litter** as the main operational unit
- optionally support **pig-level identification inside a batch**
- avoid forcing users to encode every pig with a heavy full profile

---

# 2. Final Design Decision

## Chosen Model
**Batch-first with optional lightweight pig profiling**

### Required
- Every litter/group is stored as a **batch**
- Batch contains the main inventory information

### Optional
- A batch may contain **simple pig profiles**
- Pig profiles are lightweight and mainly used for identification

### Important
Do **not** design the MVP as a full one-record-per-pig system with deep data entry requirements.

---

# 3. Functional Scope

This module must cover:

- breeder/inahin registry
- batch/litter creation
- optional auto-generation of pig profiles within a batch
- viewing and managing batches
- viewing and managing lightweight pig profiles
- batch count adjustments
- stage/status updates
- batch timeline/history
- inventory overview summaries for the President dashboard

This module should be designed so it can later integrate with:

- health/vaccination
- mortality
- sales
- expenses
- profitability
- reports

---

# 4. Main Actors

## Primary operational users
- President
- Secretary
- Treasurer

## Optional secondary user
- Officer / Encoder

## Access direction
This module is mainly under the **President operational area**, but should be designed in a way that shared access by Secretary/Treasurer can be supported later through permissions.

---

# 5. Architecture Principle

## Inventory hierarchy
Use this hierarchy:

### Level 1 — Breeder/Inahin
Used for breeder records.

### Level 2 — Batch/Litter
Main inventory record for a group of piglets/fatteners.

### Level 3 — Optional Pig Profiles
Simple per-pig identity rows inside a batch.

### Source of truth
For MVP:
- **Batch is the main source of truth for counts**
- pig profiles are supporting records for identification and exceptions

---

# 6. Domain Model

## A. Breeder
Represents a breeder sow/inahin.

### Suggested fields
- id
- breeder_code
- name_or_tag
- reproductive_status
- acquisition_date nullable
- expected_farrowing_date nullable
- notes nullable
- created_by
- timestamps
- soft deletes

---

## B. Pig Batch
Represents one litter/group.

### Suggested fields
- id
- batch_code
- breeder_id nullable
- caretaker_user_id nullable
- cycle_number nullable
- birth_date
- initial_count
- current_count
- average_weight nullable
- stage
- status
- has_pig_profiles boolean default false
- notes nullable
- last_reviewed_at nullable
- created_by
- timestamps
- soft deletes

### Purpose
This is the main record used for inventory monitoring.

---

## C. Pig
Represents a lightweight pig profile inside a batch.

### Suggested fields
- id
- batch_id
- pig_no
- ear_mark_type nullable
- ear_mark_value nullable
- sex nullable
- status
- remarks nullable
- created_by
- timestamps
- soft deletes

### Purpose
This should be minimal. It is used mainly to support pig-level identification.

---

## D. Batch Adjustment
Tracks changes in batch counts.

### Suggested fields
- id
- batch_id
- adjustment_type
- quantity_before
- quantity_change
- quantity_after
- reason
- remarks nullable
- created_by
- timestamps

### Purpose
Every count change must be logged.

---

## E. Batch Status History
Tracks stage/status changes for auditability.

### Suggested fields
- id
- batch_id
- old_stage nullable
- new_stage nullable
- old_status nullable
- new_status nullable
- remarks nullable
- changed_by
- timestamps

---

# 7. Recommended Enums / Controlled Values

## Batch stages
Use simple lifecycle stages:
- Piglet
- Weaning
- Fattening
- For Sale
- Completed

## Batch statuses
Use simple operational statuses:
- Active
- Under Monitoring
- Ready for Sale
- Sold
- Closed

## Pig statuses
- Active
- Sick
- Isolated
- Sold
- Deceased

## Adjustment types
- increase
- decrease
- correction

## Adjustment reasons
- mortality
- sale deduction
- recount
- isolated pig
- transfer
- data correction

---

# 8. Business Rules

## Rule 1
A batch represents one litter/group and cannot be merged with another batch in the MVP.

## Rule 2
A batch must always have:
- batch_code
- birth_date
- initial_count
- current_count
- stage
- status

## Rule 3
`current_count` must never be negative.

## Rule 4
If pig profiles are enabled, they should be auto-generated from the initial count when the user chooses that option.

## Rule 5
Pig profiles must remain lightweight. Do not require heavy per-pig details in MVP.

## Rule 6
Each pig number must be unique within a batch.

## Rule 7
Every inventory count adjustment must require:
- previous value
- new value or delta
- reason
- user reference

## Rule 8
A normal batch edit form must **not** silently overwrite current count. Count changes should go through a dedicated adjustment flow.

## Rule 9
Closed/completed batches should not allow normal operational edits unless reopened by an authorized user.

## Rule 10
Batch remains the source of truth for inventory counts in MVP.

---

# 9. Key UX Rule

Do not make users manually fill a long form for every pig.

## Preferred workflow
1. User creates a batch
2. User enters total pig count
3. System asks whether to enable pig profiles
4. If yes, system auto-generates pig rows
5. User only edits exceptions if needed

This reduces burden while respecting the ear-cut identification practice.

---

# 10. User Flow

## Main flow: Create batch
1. Open Inventory module
2. Click “Create Batch”
3. Fill:
   - batch code
   - breeder
   - caretaker
   - birth date
   - initial count
   - stage
   - status
   - notes
4. Choose:
   - enable pig profiles: yes/no
5. Save batch
6. If enabled:
   - system auto-generates pig profiles from the initial count
7. Redirect to batch detail page

---

## Main flow: View batch
Batch detail page should show:
- batch profile
- inventory summary
- pig profiles if enabled
- status history
- adjustment history
- action buttons

---

## Main flow: Adjust count
1. Open batch
2. Click “Adjust Count”
3. Enter:
   - type
   - quantity change
   - reason
   - remarks
4. System computes new count
5. Save adjustment
6. Adjustment is logged

---

## Main flow: Update stage/status
1. Open batch
2. Click “Update Status”
3. Select new stage and/or status
4. Add remarks if needed
5. Save
6. History row is created

---

# 11. UI Pages to Build

## 1. Inventory Overview Page
Purpose:
- module landing page
- summary cards
- quick actions
- recent or active batches

### Suggested content
- total active batches
- total piglets
- total breeders
- total fatteners
- total sick
- total deceased
- ready for sale batches
- recent inventory updates

---

## 2. Batch List Page
Purpose:
- list all batches

### Table columns
- batch code
- breeder
- caretaker
- birth date
- initial count
- current count
- stage
- status
- last updated
- actions

### Filters
- stage
- status
- breeder
- caretaker
- active/completed

---

## 3. Create Batch Page
Purpose:
- create one batch

### Must include
- optional toggle for pig profile generation

---

## 4. Batch Detail Page
Purpose:
- central page for one batch

### Sections
- batch information
- summary metrics
- pig profile list
- adjustment history
- status history
- notes

### Main action buttons
- Edit Batch
- Adjust Count
- Update Status
- Manage Pig Profiles
- Archive/Close Batch

---

## 5. Edit Batch Page
Purpose:
- edit non-count details

### Editable
- caretaker
- average weight
- stage
- status
- notes

### Do not allow
- direct count editing without adjustment log

---

## 6. Pig Profiles Page / Section
Purpose:
- manage pig-level identity rows if enabled

### Columns
- pig no.
- ear mark
- sex
- status
- remarks

---

## 7. Archived Batches Page
Purpose:
- completed/closed inventory records

---

# 12. Backend Structure Recommendation

## Suggested Laravel controllers

### `PresidentPigInventoryController`
Handles:
- index
- create
- store
- show
- edit
- update
- archive

### `PresidentPigProfileController`
Handles:
- index
- store
- update

### `PresidentPigBatchAdjustmentController`
Handles:
- store

### `PresidentPigBatchStatusController`
Handles:
- store

---

## Suggested service classes

### `CreatePigBatchService`
Responsibilities:
- create batch
- create initial status history
- call pig profile generator if enabled

### `GeneratePigProfilesService`
Responsibilities:
- generate N pig rows
- assign sequential pig numbers
- optionally populate default ear mark values

### `AdjustPigBatchCountService`
Responsibilities:
- validate count update
- change batch count safely
- create adjustment log

### `UpdatePigBatchStatusService`
Responsibilities:
- change stage/status
- create history record

---

# 13. Route Planning

## Recommended route grouping
Use role-protected routes under President module.

Example shape:

- `/president/inventory`
- `/president/inventory/create`
- `/president/inventory/{batch}`
- `/president/inventory/{batch}/edit`
- `/president/inventory/{batch}/pigs`
- `/president/inventory/{batch}/adjustments`
- `/president/inventory/{batch}/status`

The planning agent should generate route definitions and apply role middleware.

---

# 14. Database Planning Tasks for Agent

The planning agent should produce:

## Tables
- `pig_breeders`
- `pig_batches`
- `pigs`
- `pig_batch_adjustments`
- `pig_batch_status_histories`

## Constraints
- unique `breeder_code`
- unique `batch_code`
- unique composite `(batch_id, pig_no)` in `pigs`
- foreign keys for breeder, caretaker, batch references

## Recommended soft deletes
Use soft deletes for:
- breeders
- batches
- pigs

---

# 15. Validation Planning Tasks

The agent must ensure validation exists for:

## Batch creation
- required batch code
- unique batch code
- valid birth date
- initial count >= 1
- current count >= 0
- required stage
- required status

## Pig creation/update
- valid batch
- pig number required
- unique pig number per batch
- valid status

## Adjustment creation
- valid batch
- valid quantity values
- non-negative resulting count
- required reason

## Status update
- valid stage/status values

---

# 16. Reporting / Summary Expectations

The module should expose enough data for future reports, including:

- total pigs by batch
- active batch counts
- counts by stage
- counts by status
- active vs closed batches
- pig profile existence per batch

The agent should ensure schema and services are built with future report queries in mind.

---

# 17. Integration Expectations

This module must be designed to connect later to:

## Health module
Likely link by:
- `batch_id`
- optionally `pig_id`

## Mortality module
Likely link by:
- `batch_id`
- optionally `pig_id`

## Sales module
Likely link by:
- `batch_id`

## Expense module
Likely link by:
- `batch_id`
- cycle context

## Reports module
Should summarize from batch + linked records

The planning agent should not hard-couple these modules yet, but should preserve integration-ready keys.

---

# 18. Incremental Build Plan

## Phase 1 — Core batch inventory
Build first:
- breeder model/table
- batch model/table
- batch CRUD
- list and detail views

## Phase 2 — Optional pig profiles
Build next:
- pigs table/model
- auto-generate pig profiles
- pig profile list/edit

## Phase 3 — Count and status workflows
Build next:
- adjustment logs
- status history
- count adjustment flow
- status update flow

## Phase 4 — Inventory overview and filters
Build next:
- summary cards
- filters
- archived batches

## Phase 5 — Integration support
Prepare hooks for:
- health
- mortality
- sales
- reports

---

# 19. What the Planning Agent Must Avoid

Do **not** implement the following in MVP for this module:

- full detailed one-by-one pig health ledger inside inventory module
- complex breeding analytics
- batch merge/split logic
- per-pig financial accounting
- forced pig profiling for every batch
- silent current count edits
- overcomplicated UI with too many fields

---

# 20. Success Criteria

The module is successful if:

1. A user can create a batch quickly
2. A batch can exist without pig profiles
3. A batch can optionally generate simple pig profiles
4. Inventory counts are tracked safely
5. Stage/status changes are logged
6. The UI remains simple enough for rural officers
7. The schema is ready for later integration with health, mortality, sales, and reports

---

# 21. Deliverables the Planning Agent Should Produce

The agent should generate these implementation artifacts:

## Backend
- migrations
- models
- relationships
- form requests
- controllers
- services
- policies/middleware plan

## Frontend
- page/component list
- form structure
- table layout
- filters
- overview widgets


---

# 22. Final Instruction to Planning Agent

Implement this module as a **hybrid, batch-centered inventory system**.

### Absolute priorities
- preserve client workflow
- minimize user burden
- keep batch as the primary inventory unit
- make pig profiling optional and lightweight
- log count changes properly
- keep the UI simple and mobile-friendly

### Final implementation philosophy
**Simple first, traceable second, expandable later.**