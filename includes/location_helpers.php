<?php
/**
 * Location Helper Functions
 * Functions to populate dropdowns with sub counties and wards
 */

/**
 * Get all sub counties from database
 * @return array
 */
function get_all_sub_counties() {
    $db = db();
    return $db->fetchAll("
        SELECT id, name, code 
        FROM sub_counties 
        ORDER BY name ASC
    ");
}

/**
 * Get all wards from database with sub county info
 * @return array
 */
function get_all_wards() {
    $db = db();
    return $db->fetchAll("
        SELECT w.id, w.name, w.code, w.sub_county_id, sc.name as sub_county_name
        FROM wards w 
        JOIN sub_counties sc ON w.sub_county_id = sc.id 
        ORDER BY sc.name ASC, w.name ASC
    ");
}

/**
 * Get wards for a specific sub county
 * @param int $sub_county_id
 * @return array
 */
function get_wards_by_sub_county($sub_county_id) {
    $db = db();
    return $db->fetchAll("
        SELECT w.id, w.name, w.code, w.sub_county_id
        FROM wards w 
        WHERE w.sub_county_id = ?
        ORDER BY w.name ASC
    ", [$sub_county_id]);
}

/**
 * Generate sub county dropdown HTML
 * @param string $selected_id Currently selected sub county ID
 * @param string $name Field name attribute
 * @param string $id Field id attribute
 * @param bool $required Whether field is required
 * @return string HTML for dropdown
 */
function generate_sub_county_dropdown($selected_id = '', $name = 'sub_county_id', $id = 'sub_county_id', $required = true) {
    $sub_counties = get_all_sub_counties();
    $required_attr = $required ? 'required' : '';
    
    $html = "<select class='form-control' id='{$id}' name='{$name}' {$required_attr} onchange='filterWards()'>";
    $html .= "<option value=''>Select Sub County</option>";
    
    foreach ($sub_counties as $sub_county) {
        $selected = ($selected_id == $sub_county['id']) ? 'selected' : '';
        $html .= "<option value='{$sub_county['id']}' {$selected}>" . htmlspecialchars($sub_county['name']) . "</option>";
    }
    
    $html .= "</select>";
    return $html;
}

/**
 * Generate ward dropdown HTML
 * @param string $selected_id Currently selected ward ID
 * @param string $name Field name attribute
 * @param string $id Field id attribute
 * @param bool $required Whether field is required
 * @return string HTML for dropdown
 */
function generate_ward_dropdown($selected_id = '', $name = 'ward_id', $id = 'ward_id', $required = true) {
    $wards = get_all_wards();
    $required_attr = $required ? 'required' : '';
    
    $html = "<select class='form-control' id='{$id}' name='{$name}' {$required_attr}>";
    $html .= "<option value=''>Select Ward</option>";
    
    foreach ($wards as $ward) {
        $selected = ($selected_id == $ward['id']) ? 'selected' : '';
        $html .= "<option value='{$ward['id']}' data-sub-county='{$ward['sub_county_id']}' {$selected}>" . htmlspecialchars($ward['name']) . "</option>";
    }
    
    $html .= "</select>";
    return $html;
}

/**
 * Generate JavaScript for ward filtering
 * @return string JavaScript code
 */
function generate_ward_filter_js() {
    $wards = get_all_wards();
    
    $js = "<script>
    const wardsData = " . json_encode($wards) . ";
    
    function filterWards() {
        const subCountySelect = document.getElementById('sub_county_id');
        const wardSelect = document.getElementById('ward_id');
        const selectedSubCounty = subCountySelect.value;
        
        // Clear ward selection
        wardSelect.innerHTML = '<option value=\"\">Select Ward</option>';
        
        if (selectedSubCounty) {
            // Filter wards by sub county
            const filteredWards = wardsData.filter(ward => ward.sub_county_id == selectedSubCounty);
            
            filteredWards.forEach(ward => {
                const option = document.createElement('option');
                option.value = ward.id;
                option.textContent = ward.name;
                option.setAttribute('data-sub-county', ward.sub_county_id);
                wardSelect.appendChild(option);
            });
        }
    }
    </script>";
    
    return $js;
}
?>
