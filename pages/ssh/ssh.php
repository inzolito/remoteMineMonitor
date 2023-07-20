<?php
/*
 $cod="
 TRUNCATE TABLE plan_items
 TRUNCATE TABLE grade_restrictions
 TRUNCATE TABLE PPTO_UEBD_CARGUIO
 TRUNCATE TABLE worker_vacations
 TRUNCATE TABLE shift_dumps
 TRUNCATE TABLE shift_gps
 TRUNCATE TABLE locations
 TRUNCATE TABLE lp_mine
 TRUNCATE TABLE by_truck_loads
 TRUNCATE TABLE aspnet_Applications
 TRUNCATE TABLE configurations
 TRUNCATE TABLE shift_trams
 TRUNCATE TABLE plans
 TRUNCATE TABLE grades
 TRUNCATE TABLE current_trucks_at_shovel
 TRUNCATE TABLE workers
 TRUNCATE TABLE shift_gps_local_regadores
 TRUNCATE TABLE shift_explosives
 TRUNCATE TABLE maintenance
 TRUNCATE TABLE lp_scenarios
 TRUNCATE TABLE current_shovel_materials
 TRUNCATE TABLE dragline_operation
 TRUNCATE TABLE current_detail_loads
 TRUNCATE TABLE shift_vims
 TRUNCATE TABLE hauling
 TRUNCATE TABLE dashboard_activity
 TRUNCATE TABLE shift_gps_local
 TRUNCATE TABLE shift_fuels
 TRUNCATE TABLE month_blast_dumps
 TRUNCATE TABLE hour_detail_buckets
 TRUNCATE TABLE custom_by_states_detail
 TRUNCATE TABLE drawing_elements
 TRUNCATE TABLE shift_heading_rounds
 TRUNCATE TABLE shift_waterings
 TRUNCATE TABLE projection_specs
 TRUNCATE TABLE heading_rounds
 TRUNCATE TABLE dashboard_availability
 TRUNCATE TABLE shift_geology_passdown
 TRUNCATE TABLE month_blast_loads
 TRUNCATE TABLE aspnet_Users
 TRUNCATE TABLE manbus_assignments
 TRUNCATE TABLE hour_detail_dozer
 TRUNCATE TABLE drawings
 TRUNCATE TABLE shift_loads
 TRUNCATE TABLE dashboard_times
 TRUNCATE TABLE DETALLE_GEOLOGIA_SCM
 TRUNCATE TABLE shift_waters
 TRUNCATE TABLE projects
 TRUNCATE TABLE help
 TRUNCATE TABLE dashboard_drill_hole
 TRUNCATE TABLE PPTO_UEBD
 TRUNCATE TABLE shift_dumps_prueba
 TRUNCATE TABLE month_cycle_time
 TRUNCATE TABLE material_tonnages
 TRUNCATE TABLE hour_detail_drillholes
 TRUNCATE TABLE drill_blasts
 TRUNCATE TABLE shift_logins
 TRUNCATE TABLE device_components
 TRUNCATE TABLE shift_weights
 TRUNCATE TABLE hms_events
 TRUNCATE TABLE dashboard_load
 TRUNCATE TABLE shift_loads_prueba
 TRUNCATE TABLE shift_grade_samples
 TRUNCATE TABLE month_detail_dumps
 TRUNCATE TABLE aspnet_SchemaVersions
 TRUNCATE TABLE hour_detail_dumps
 TRUNCATE TABLE drill_design_details
 TRUNCATE TABLE devices
 TRUNCATE TABLE shift_work_zones
 TRUNCATE TABLE replicas
 TRUNCATE TABLE PM_UEBD_CAEX
 TRUNCATE TABLE dashboard_reasons
 TRUNCATE TABLE shift_hauls
 TRUNCATE TABLE month_detail_loads
 TRUNCATE TABLE hour_detail_loads
 TRUNCATE TABLE shift_maintenance
 TRUNCATE TABLE digging
 TRUNCATE TABLE road_conditions
 TRUNCATE TABLE dashboard_shovel_cycle
 TRUNCATE TABLE month_dump_dumps
 TRUNCATE TABLE hour_detail_offsets
 TRUNCATE TABLE exception_routing2
 TRUNCATE TABLE drill_kpis
 TRUNCATE TABLE shift_material_quality
 TRUNCATE TABLE dragline_buckets
 TRUNCATE TABLE shovel_restrictions
 TRUNCATE TABLE dashboard_states
 TRUNCATE TABLE shift_hms
 TRUNCATE TABLE month_equipment_availability
 TRUNCATE TABLE month_detail_buckets
 TRUNCATE TABLE hour_detail_trams
 TRUNCATE TABLE shift_material_skips
 TRUNCATE TABLE aspnet_Membership
 TRUNCATE TABLE drill_hole_tag_mappings
 TRUNCATE TABLE rotations
 TRUNCATE TABLE DETALLE_TIEMPOS_ESTADOS_PO
 TRUNCATE TABLE shift_gps_local_normalizada
 TRUNCATE TABLE shift_info
 TRUNCATE TABLE month_equipment_times
 TRUNCATE TABLE hour_dump_dumps
 TRUNCATE TABLE active_lines
 TRUNCATE TABLE drill_parts_maintenances
 TRUNCATE TABLE shift_offlines
 TRUNCATE TABLE drill_holes
 TRUNCATE TABLE snapshots
 TRUNCATE TABLE route_restrictions
 TRUNCATE TABLE by_detail_loads
 TRUNCATE TABLE dashboard_truck_cycle
 TRUNCATE TABLE shift_labours
 TRUNCATE TABLE month_grade_dumps
 TRUNCATE TABLE hour_equipment_reasons
 TRUNCATE TABLE shift_prestarts
 TRUNCATE TABLE drill_parts
 TRUNCATE TABLE spillage
 TRUNCATE TABLE rpcs
 TRUNCATE TABLE asset_tracking
 TRUNCATE TABLE by_detail_dumps
 TRUNCATE TABLE by_shovel_operator_loads
 TRUNCATE TABLE month_grade_loads
 TRUNCATE TABLE hour_equipment_states
 TRUNCATE TABLE drill_points
 TRUNCATE TABLE shift_ripping
 TRUNCATE TABLE drill_patterns
 TRUNCATE TABLE summaries
 TRUNCATE TABLE rules
 TRUNCATE TABLE assignments
 TRUNCATE TABLE by_blast_dumps
 TRUNCATE TABLE shift_locata
 TRUNCATE TABLE month_material_dumps
 TRUNCATE TABLE hour_equipment_times
 TRUNCATE TABLE drill_transactions
 TRUNCATE TABLE shift_roads
 TRUNCATE TABLE summary_times
 TRUNCATE TABLE schema_info
 TRUNCATE TABLE drilling
 TRUNCATE TABLE breaks
 TRUNCATE TABLE by_blast_loads
 TRUNCATE TABLE month_material_loads
 TRUNCATE TABLE hour_shovel_loads
 TRUNCATE TABLE shift_safety_events
 TRUNCATE TABLE average_speeds
 TRUNCATE TABLE table_stamps
 TRUNCATE TABLE sensor_alarms
 TRUNCATE TABLE dumping
 TRUNCATE TABLE by_cycle_time
 TRUNCATE TABLE shift_lp
 TRUNCATE TABLE month_shovel_dumps
 TRUNCATE TABLE hour_truck_dumps
 TRUNCATE TABLE shift_sensors
 TRUNCATE TABLE beacons
 TRUNCATE TABLE aspnet_Profile
 TRUNCATE TABLE todo_requests
 TRUNCATE TABLE enum_tables
 TRUNCATE TABLE by_detail_buckets
 TRUNCATE TABLE by_truck_operator_dumps
 TRUNCATE TABLE month_shovel_idle
 TRUNCATE TABLE hour_truck_hauls
 TRUNCATE TABLE enum_attributes
 TRUNCATE TABLE shift_sims_eocs
 TRUNCATE TABLE logoImage
 TRUNCATE TABLE blend_models
 TRUNCATE TABLE todos
 TRUNCATE TABLE sensor_groups
 TRUNCATE TABLE equipment
 TRUNCATE TABLE by_dump_dumps
 TRUNCATE TABLE by_truck_operator_loads
 TRUNCATE TABLE event_logs
 TRUNCATE TABLE shift_mems
 TRUNCATE TABLE month_shovel_loads
 TRUNCATE TABLE hour_truck_loads
 TRUNCATE TABLE enum_categories
 TRUNCATE TABLE shift_state_extents
 TRUNCATE TABLE blending
 TRUNCATE TABLE topography
 TRUNCATE TABLE sensor_sets
 TRUNCATE TABLE equipment_lasts
 TRUNCATE TABLE by_equipment_availability
 TRUNCATE TABLE change_configurations
 TRUNCATE TABLE shift_messages
 TRUNCATE TABLE month_truck_dumps
 TRUNCATE TABLE by_custom_detail_activity
 TRUNCATE TABLE shift_states
 TRUNCATE TABLE boundaries
 TRUNCATE TABLE aspnet_Roles
 TRUNCATE TABLE topography_quads
 TRUNCATE TABLE sensor_type_map
 TRUNCATE TABLE features
 TRUNCATE TABLE by_equipment_reasons
 TRUNCATE TABLE shift_names
 TRUNCATE TABLE month_truck_hauls
 TRUNCATE TABLE month_operator_times
 TRUNCATE TABLE by_custom_detail_cycle_time
 TRUNCATE TABLE shovel_kpis
 TRUNCATE TABLE tracking_tags
 TRUNCATE TABLE sensor_units
 TRUNCATE TABLE fuel_rates
 TRUNCATE TABLE by_equipment_states
 TRUNCATE TABLE charge_codes
 TRUNCATE TABLE aspnet_UsersInRoles
 TRUNCATE TABLE month_truck_loads
 TRUNCATE TABLE by_custom_cycle_time_detail
 TRUNCATE TABLE equipment_components
 TRUNCATE TABLE sims_configs
 TRUNCATE TABLE buckets
 TRUNCATE TABLE tramming
 TRUNCATE TABLE sensors
 TRUNCATE TABLE geometry_node_sensors
 TRUNCATE TABLE by_equipment_times
 TRUNCATE TABLE charging
 TRUNCATE TABLE prestart_checks
 TRUNCATE TABLE custom_by_hourmeter_details
 TRUNCATE TABLE equipment_geometries
 TRUNCATE TABLE tire_axles
 TRUNCATE TABLE tramming_schedules
 TRUNCATE TABLE shift_activities
 TRUNCATE TABLE geometry_node_sets
 TRUNCATE TABLE hp_calibrations
 TRUNCATE TABLE by_grade_dumps
 TRUNCATE TABLE config_shifts
 TRUNCATE TABLE iterate
 TRUNCATE TABLE shift_restarts
 TRUNCATE TABLE reasons
 TRUNCATE TABLE by_drill_holes
 TRUNCATE TABLE erp_references
 TRUNCATE TABLE tire_dismount_details
 TRUNCATE TABLE shift_alarm_annotation
 TRUNCATE TABLE geometry_nodes
 TRUNCATE TABLE hp_gps_positions
 TRUNCATE TABLE by_grade_loads
 TRUNCATE TABLE tire_models
 TRUNCATE TABLE by_custom_cycle_time
 TRUNCATE TABLE Time
 TRUNCATE TABLE shift_blasting
 TRUNCATE TABLE geometry_param_sets
 TRUNCATE TABLE hp_gps_trucks
 TRUNCATE TABLE by_material_dumps
 TRUNCATE TABLE costs
 TRUNCATE TABLE shift_road_conditions
 TRUNCATE TABLE exception_routing
 TRUNCATE TABLE tire_transaction_dismounts
 TRUNCATE TABLE roads
 TRUNCATE TABLE aspnet_Paths
 TRUNCATE TABLE truck_restrictions
 TRUNCATE TABLE shift_boundaries
 TRUNCATE TABLE geometry_params
 TRUNCATE TABLE images
 TRUNCATE TABLE by_material_loads
 TRUNCATE TABLE crushing
 TRUNCATE TABLE shift_road_times
 TRUNCATE TABLE explosive_truck_kpis
 TRUNCATE TABLE tire_transactions
 TRUNCATE TABLE safety_events
 TRUNCATE TABLE users
 TRUNCATE TABLE inbox
 TRUNCATE TABLE by_operator_activities
 TRUNCATE TABLE old_grades
 TRUNCATE TABLE shift_sensor_alarm_metadata
 TRUNCATE TABLE moving_averages
 TRUNCATE TABLE explosives
 TRUNCATE TABLE tires
 TRUNCATE TABLE sensor_configs
 TRUNCATE TABLE aspnet_PersonalizationAllUsers
 TRUNCATE TABLE view_elements
 TRUNCATE TABLE shift_charge_codes
 TRUNCATE TABLE by_operator_reasons
 TRUNCATE TABLE nicknames
 TRUNCATE TABLE topography_quad_timestamps
 TRUNCATE TABLE views
 TRUNCATE TABLE shift_comments
 TRUNCATE TABLE shift_breaks
 TRUNCATE TABLE aspnet_PersonalizationPerUser
 TRUNCATE TABLE local_snapshots
 TRUNCATE TABLE by_operator_states
 TRUNCATE TABLE paso2
 TRUNCATE TABLE shift_services
 TRUNCATE TABLE novatel_auth_codes
 TRUNCATE TABLE freewave_configurations
 TRUNCATE TABLE topography_quad_updates
 TRUNCATE TABLE vims_channels
 TRUNCATE TABLE DETALLE_TIEMPOS_ESTADOS
 TRUNCATE TABLE shift_buckets
 TRUNCATE TABLE replicas_bkp
 TRUNCATE TABLE locals
 TRUNCATE TABLE by_operator_times
 TRUNCATE TABLE DEMORASMAX_PERF
 TRUNCATE TABLE dig_rates
 TRUNCATE TABLE shift_shovel_weights
 TRUNCATE TABLE operator_preferences
 TRUNCATE TABLE fuel_consumption
 TRUNCATE TABLE traveling
 TRUNCATE TABLE vims_events
 TRUNCATE TABLE Calendar
 TRUNCATE TABLE shift_defects
 TRUNCATE TABLE locatas
 TRUNCATE TABLE by_shovel_dumps
 TRUNCATE TABLE shift_snapshots
 TRUNCATE TABLE outbox
 TRUNCATE TABLE fueling
 TRUNCATE TABLE sysdiagrams
 TRUNCATE TABLE truck_kpis
 TRUNCATE TABLE vims_readers
 TRUNCATE TABLE shift_dozing
 TRUNCATE TABLE by_shovel_idle
 TRUNCATE TABLE display_selectors
 TRUNCATE TABLE shift_spillage
 TRUNCATE TABLE phrases
 TRUNCATE TABLE geometry_points
 TRUNCATE TABLE usage_analytics
 TRUNCATE TABLE vims_units
 TRUNCATE TABLE shift_consumables
 TRUNCATE TABLE lp_events
 TRUNCATE TABLE by_shovel_loads
 TRUNCATE TABLE display_styles
 TRUNCATE TABLE plan_descriptors
 TRUNCATE TABLE geometry_segments
 TRUNCATE TABLE weighing
 TRUNCATE TABLE Query
 TRUNCATE TABLE shift_dragline_steps
 TRUNCATE TABLE shift_dragline_cycles
 TRUNCATE TABLE lp_feed_rates
 TRUNCATE TABLE by_truck_dumps;
 TRUNCATE TABLE dozer_kpis
 TRUNCATE TABLE aspnet_WebEvent_Events
 TRUNCATE TABLE change_logs
 TRUNCATE TABLE shift_tkph
 TRUNCATE TABLE plan_elements
 TRUNCATE TABLE grade_qualities
 TRUNCATE TABLE worker_qualifications
 TRUNCATE TABLE shift_drills
 TRUNCATE TABLE shift_dump_extents
 TRUNCATE TABLE languages
 TRUNCATE TABLE lp_forecasts
 TRUNCATE TABLE by_truck_hauls
 TRUNCATE TABLE dozing
 TRUNCATE TABLE Query2
 TRUNCATE TABLE composite_materials
 TRUNCATE TABLE shift_topography_quads
 TRUNCATE TABLE RoleReportAccess
"; 
$cod = "$cod";
$array = explode("TRUNCATE", $cod);
foreach ($array as $element) {
    echo "TRUNCATE ".$element . " ; <br>";
}
*/

?>


<div class="card card-info">
    <div class="card-header">
        <div class="card-tittle">
            Busqueda
        </div>
    </div>

    <div class="card-body">
 
        <div class="row">

            <div class="col-md-10">
                <input id="txtBuscar" name="txtBuscar" class="form-control">
            </div>
            <div class="col-md-2">
                <button class="btn bg-danger btn-block" name="btnBuscar" id="btnBuscar">
                    <i class="fa-solid fa-magnifying-glass"></i> Buscar</button>
            </div>
        
        </div>
    </div>

</div>




<div class="card card-info">
    <div class="card-header">
        <div class="card-tittle">
            Lista de problemas
        </div>
    </div>

    <div class="card-body">
 
        <div class="row">
            <table class="table ">
                <thead>
                    <tr>    
                        <th>Problema</th>
                        <th>Acción</th>
                    </tr>
                </thead>

                <tbody>
                <?php
                    for($x=0; $x<30 ; $x++)
                    {
                ?>
                    <tr>
                        <td>GPS perfo no aparece. Sale unknow </td>
                        <td> <button class="btn bg-success btn-block" name="btnBuscar" id="btnBuscar">Ver</button>
                        </td>
                    </tr>
                <?php
                 }
                ?>
                </tbody>
            </table>

        
        </div>
    </div>

</div>