<?php
require_once 'db.php';

/**
 * Fetches all projects for the project list
 * @return array
 */
function getAllProjects() {
    $conn = getDBConnection();
    $result = $conn->query("SELECT project_id, project_name, manager, location FROM projects ORDER BY project_name");
    
    $projects = [];
    while ($row = $result->fetch_assoc()) {
        $projects[] = $row;
    }
    
    $conn->close();
    return $projects;
}

/**
 * Fetches a single project with its resources by project ID
 * @param int $project_id
 * @return array|null
 */
function getProjectById($project_id) {
    $conn = getDBConnection();
    
    // Sanitise input
    $project_id = intval($project_id);
    
    // Get project details
    $stmt = $conn->prepare("SELECT * FROM projects WHERE project_id = ?");
    $stmt->bind_param("i", $project_id);
    $stmt->execute();
    $project = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    if (!$project) {
        $conn->close();
        return null;
    }
    
    // Get resources for this project
    $stmt = $conn->prepare("
        SELECT r.resource_id, r.resource_type, r.conditions_of_use 
        FROM resources r
        JOIN project_resources pr ON r.resource_id = pr.resource_id
        WHERE pr.project_id = ?
        ORDER BY r.resource_type
    ");
    $stmt->bind_param("i", $project_id);
    $stmt->execute();
    $resources = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    
    $project['resources'] = $resources;
    
    $conn->close();
    return $project;
}

// Only handle direct API calls, not when included by another file
if (basename(__FILE__) === basename($_SERVER['SCRIPT_FILENAME']) && isset($_GET['project_id'])) {
    header('Content-Type: application/json');
    $project = getProjectById($_GET['project_id']);
    
    if ($project) {
        echo json_encode($project);
    } else {
        http_response_code(404);
        echo json_encode(['error' => 'Project not found']);
    }
    exit;
}
?>