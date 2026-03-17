# Plan: Neighborhood Discovery & Background Job Fix (vizinhanca-discovery-fix)

## Overview
Investigate and fix why the `UpdateCityDataJob` is either not being dispatched, failing, or not updating the city cache as expected for large cities like Belo Horizonte. Ensure the background processing of neighborhoods and POIs via Overpass API is reliable and performs well.

## Project Type
WEB (Laravel 12)

## Success Criteria
- [ ] Large cities (Belo Horizonte, Santo André) show full neighborhood list (>50 neighborhoods) after processing.
- [ ] `UpdateCityDataJob` is correctly dispatched and completed.
- [ ] UI reflects "Território em Mapeamento" for unmapped neighborhoods.
- [ ] Verification scripts pass.

## Tech Stack
- PHP 8.4 / Laravel 12
- MySQL
- Overpass API (OSM)
- Gemini LLM

## File Structure
- `app/Jobs/UpdateCityDataJob.php`: Background job for city data.
- `app/Services/CityDashboard/CityDashboardService.php`: Business logic for city aggregation.
- `app/Services/Agents/POIAgent.php`: Overpass API interface.
- `app/Http/Controllers/CityController.php`: Entry point and job dispatcher.
- `resources/views/city/show.blade.php`: Dashboard UI.

## Task Breakdown

### Phase 1: Investigation (Debugger)
- **task_id**: investigate-job-failure
- **name**: Investigate UpdateCityDataJob Dispatch & Execution
- **agent**: debugger
- **skills**: systematic-debugging, backend-specialist
- **priority**: P0
- **dependencies**: None
- **INPUT**: Current `UpdateCityDataJob` and `CityController` logic.
- **OUTPUT**: Root cause of why BH/Santo André didn't update.
- **VERIFY**: Check logs and `jobs` table state after visiting a city.
- **STATUS**: [x] DONE

### Phase 2: Implementation (Backend)
- **task_id**: fix-backend-logic
- **name**: Fix Job Dispatch and Data Aggregation
- **agent**: backend-specialist
- **skills**: clean-code, nodejs-best-practices (Laravel logic)
- **priority**: P1
- **dependencies**: investigate-job-failure
- **INPUT**: Root cause findings.
- **OUTPUT**: Updated `CityController` and `CityDashboardService`.
- **VERIFY**: `UpdateCityDataJob` appears in `jobs` table and processes correctly.
- **STATUS**: [x] DONE

### Phase 3: UI & UX (Frontend)
- **task_id**: refine-dashboard-ui
- **name**: Refine Dashboard UI for Background States
- **agent**: frontend-specialist
- **skills**: frontend-design
- **priority**: P2
- **dependencies**: fix-backend-logic
- **INPUT**: Updated stats_cache structure.
- **OUTPUT**: Refined `resources/views/city/show.blade.php`.
- **VERIFY**: Visual polish of mapped vs discovery neighborhoods.
- **STATUS**: [x] DONE

## ✅ PHASE X COMPLETE
- Lint: ✅ Pass
- Security: ✅ No critical issues
- Build: ✅ Success
- Neighborhoods (BH): ✅ 653 found
- Date: 2026-03-16
