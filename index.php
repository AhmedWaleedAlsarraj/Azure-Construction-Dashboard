<?php
/**Homepage - displays all construction projects as selectable cards*/
require_once 'php/get_project.php';

$pageTitle = 'Projects';
$projects = getAllProjects();

include 'includes/header.php';
?>

<h2>Construction Projects</h2>
<p class="project-meta">Select a project to view location, weather, and air quality data.</p>

<?php if (empty($projects)): ?>
    <p>No projects found.</p>
<?php else: ?>
    <div class="project-grid">
        <?php foreach ($projects as $project): ?>
            <a class="project-card" href="project.php?project_id=<?php echo $project['project_id']; ?>">
                <h3><?php echo htmlspecialchars($project['project_name']); ?></h3>
                <p>📍 <?php echo htmlspecialchars($project['location']); ?></p>
                <p>👤 <?php echo htmlspecialchars($project['manager']); ?></p>
            </a>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php include 'includes/footer.php'; ?>