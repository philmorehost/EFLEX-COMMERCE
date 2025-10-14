<?php
// Include the new admin header
include 'includes/admin_header.php';
require_permission('manage_hero_slider');

$message = "";

// Function to handle image upload
function handle_hero_image_upload($file_input_name) {
    if(isset($_FILES[$file_input_name]) && $_FILES[$file_input_name]["error"] == 0){
        $allowed = ["jpg" => "image/jpeg", "png" => "image/png", "gif" => "image/gif"];
        $filename = $_FILES[$file_input_name]["name"];
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

        if(!array_key_exists($ext, $allowed)) {
            return [ "error" => "Invalid file type for hero image." ];
        }

        $new_filename = "hero_" . uniqid() . "." . $ext;
        $upload_path = "../uploads/" . $new_filename;

        if(move_uploaded_file($_FILES[$file_input_name]["tmp_name"], $upload_path)){
            return [ "success" => 'uploads/' . $new_filename ];
        } else {
            return [ "error" => "Error uploading hero image." ];
        }
    }
    return [ "no_file" => true ];
}

// Handle Add/Edit Slide
if($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['save_slide'])){
    $id = $_POST['id'];
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $type = $_POST['type'];
    $content_url = trim($_POST['content_url']);
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    $sort_order = (int)$_POST['sort_order'];
    $overlay_color = $_POST['overlay_color'];
    $overlay_opacity = (float)$_POST['overlay_opacity'];

    if($type === 'image'){
        $upload_result = handle_hero_image_upload('image_file');
        if(isset($upload_result['success'])){
            $content_url = $upload_result['success'];
        } elseif(isset($upload_result['error'])) {
            $message = '<div class="alert alert-danger">' . $upload_result['error'] . '</div>';
        }
    }

    if(empty($message)){
        if(empty($id)){ // Add new slide
            $sql = "INSERT INTO hero_slides (title, description, overlay_color, overlay_opacity, type, content_url, is_active, sort_order) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            if($stmt = $mysqli->prepare($sql)){
                $stmt->bind_param("ssssssii", $title, $description, $overlay_color, $overlay_opacity, $type, $content_url, $is_active, $sort_order);
                if($stmt->execute()){
                    $message = '<div class="alert alert-success">Slide added successfully.</div>';
                } else {
                    $message = '<div class="alert alert-danger">Error adding slide.</div>';
                }
                $stmt->close();
            }
        } else { // Update existing slide
            $sql = "UPDATE hero_slides SET title=?, description=?, overlay_color=?, overlay_opacity=?, type=?, content_url=?, is_active=?, sort_order=? WHERE id=?";
            if($stmt = $mysqli->prepare($sql)){
                $stmt->bind_param("ssssssiii", $title, $description, $overlay_color, $overlay_opacity, $type, $content_url, $is_active, $sort_order, $id);
                if($stmt->execute()){
                    $message = '<div class="alert alert-success">Slide updated successfully.</div>';
                } else {
                    $message = '<div class="alert alert-danger">Error updating slide.</div>';
                }
                $stmt->close();
            }
        }
    }
}

// Handle Delete Slide
if(isset($_GET['delete'])){
    $id = $_GET['delete'];
    $sql_get = "SELECT type, content_url FROM hero_slides WHERE id = ?";
    $stmt_get = $mysqli->prepare($sql_get);
    $stmt_get->bind_param("i", $id);
    $stmt_get->execute();
    $stmt_get->bind_result($type, $content_url);
    $stmt_get->fetch();
    $stmt_get->close();

    $sql_delete = "DELETE FROM hero_slides WHERE id = ?";
    if($stmt_delete = $mysqli->prepare($sql_delete)){
        $stmt_delete->bind_param("i", $id);
        if($stmt_delete->execute()){
            if($type === 'image' && $content_url && file_exists("../" . $content_url)){
                unlink("../" . $content_url);
            }
            header("location: manage_hero.php");
            exit();
        } else {
            $message = '<div class="alert alert-danger">Error deleting slide.</div>';
        }
        $stmt_delete->close();
    }
}

// Check if we are in edit mode
$is_edit_mode = false;
$edit_slide = ['id' => '', 'title' => '', 'description' => '', 'overlay_color' => '#000000', 'overlay_opacity' => 0.5, 'type' => 'image', 'content_url' => '', 'is_active' => 1, 'sort_order' => 0];
if(isset($_GET['edit'])){
    $is_edit_mode = true;
    $id = $_GET['edit'];
    $sql = "SELECT * FROM hero_slides WHERE id = ?";
    if($stmt = $mysqli->prepare($sql)){
        $stmt->bind_param("i", $id);
        if($stmt->execute()){
            $result = $stmt->get_result();
            if($result->num_rows == 1){
                $edit_slide = $result->fetch_assoc();
            }
        }
        $stmt->close();
    }
}

// Fetch all slides for display
$sql = "SELECT * FROM hero_slides ORDER BY sort_order ASC";
$result = $mysqli->query($sql);
$slides = $result->fetch_all(MYSQLI_ASSOC);
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h2>Manage Hero Slider</h2>
</div>

<?php echo $message; ?>

<!-- Form for Add/Edit -->
<div class="card shadow mb-4">
    <div class="card-header"><?php echo $is_edit_mode ? 'Edit Slide' : 'Add New Slide'; ?></div>
    <div class="card-body">
        <form action="manage_hero.php" method="post" enctype="multipart/form-data">
            <input type="hidden" name="id" value="<?php echo $edit_slide['id']; ?>">
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="title" class="form-label">Title (optional)</label>
                    <input type="text" name="title" id="title" class="form-control" value="<?php echo htmlspecialchars($edit_slide['title']); ?>">
                </div>
                <div class="col-md-6 mb-3">
                    <label for="type" class="form-label">Type</label>
                    <select name="type" id="type" class="form-select">
                        <option value="image" <?php if($edit_slide['type'] == 'image') echo 'selected'; ?>>Image</option>
                        <option value="video" <?php if($edit_slide['type'] == 'video') echo 'selected'; ?>>Video (YouTube URL)</option>
                    </select>
                </div>
            </div>
            <div class="mb-3">
                <label for="description" class="form-label">Description (optional)</label>
                <textarea name="description" id="description" class="form-control" rows="2"><?php echo htmlspecialchars($edit_slide['description']); ?></textarea>
            </div>
             <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="overlay_color" class="form-label">Overlay Color</label>
                    <input type="color" name="overlay_color" id="overlay_color" class="form-control form-control-color" value="<?php echo htmlspecialchars($edit_slide['overlay_color']); ?>">
                </div>
                <div class="col-md-6 mb-3">
                    <label for="overlay_opacity" class="form-label">Overlay Opacity (0.0 - 1.0)</label>
                    <input type="number" name="overlay_opacity" id="overlay_opacity" class="form-control" value="<?php echo htmlspecialchars($edit_slide['overlay_opacity']); ?>" min="0" max="1" step="0.1">
                </div>
            </div>
            <div class="mb-3" id="image-input">
                <label for="image_file" class="form-label">Image File</label>
                <input type="file" name="image_file" id="image_file" class="form-control">
                <div class="form-text">Recommended size: 1920x1080px for best results.</div>
                <?php if($is_edit_mode && $edit_slide['type'] == 'image' && !empty($edit_slide['content_url'])): ?>
                    <div class="mt-2">Current: <img src="../<?php echo htmlspecialchars($edit_slide['content_url']); ?>" style="height: 60px;"></div>
                    <input type="hidden" name="content_url" value="<?php echo htmlspecialchars($edit_slide['content_url']); ?>">
                <?php endif; ?>
            </div>
            <div class="mb-3" id="video-input" style="display: none;">
                <label for="content_url_video" class="form-label">YouTube Video URL</label>
                <input type="url" name="content_url" id="content_url_video" class="form-control" value="<?php if($edit_slide['type'] == 'video') echo htmlspecialchars($edit_slide['content_url']); ?>">
            </div>
            <div class="row">
                 <div class="col-md-6 mb-3">
                    <label for="sort_order" class="form-label">Sort Order</label>
                    <input type="number" name="sort_order" id="sort_order" class="form-control" value="<?php echo $edit_slide['sort_order']; ?>">
                </div>
                <div class="col-md-6 mb-3 d-flex align-items-center">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="is_active" id="is_active" value="1" <?php if($edit_slide['is_active']) echo 'checked'; ?>>
                        <label class="form-check-label" for="is_active">Active</label>
                    </div>
                </div>
            </div>
            <button class="btn btn-primary" type="submit" name="save_slide"><?php echo $is_edit_mode ? 'Update Slide' : 'Add Slide'; ?></button>
            <?php if($is_edit_mode): ?>
                <a href="manage_hero.php" class="btn btn-secondary">Cancel Edit</a>
            <?php endif; ?>
        </form>
    </div>
</div>

<!-- Slides Table -->
<div class="card shadow">
    <div class="card-header">Existing Slides</div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Sort</th>
                        <th>Preview</th>
                        <th>Title</th>
                        <th>Type</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(count($slides) > 0): ?>
                        <?php foreach ($slides as $slide): ?>
                        <tr>
                            <td><?php echo $slide['sort_order']; ?></td>
                            <td>
                                <?php if($slide['type'] == 'image'): ?>
                                    <img src="../<?php echo htmlspecialchars($slide['content_url']); ?>" alt="Slide" style="width: 150px; object-fit: cover;">
                                <?php else: ?>
                                    <a href="<?php echo htmlspecialchars($slide['content_url']); ?>" target="_blank">Video Link</a>
                                <?php endif; ?>
                            </td>
                            <td><?php echo htmlspecialchars($slide['title']); ?></td>
                            <td><?php echo ucfirst($slide['type']); ?></td>
                            <td><?php echo $slide['is_active'] ? '<span class="badge bg-success">Active</span>' : '<span class="badge bg-danger">Inactive</span>'; ?></td>
                            <td class="text-end">
                                <a href="manage_hero.php?edit=<?php echo $slide['id']; ?>" class="btn btn-sm btn-warning">Edit</a>
                                <a href="manage_hero.php?delete=<?php echo $slide['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure?')">Delete</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="6" class="text-center">No slides found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const typeSelect = document.getElementById('type');
    const imageInput = document.getElementById('image-input');
    const videoInput = document.getElementById('video-input');
    const videoUrlInput = document.getElementById('content_url_video');
    const imageUrlInput = document.querySelector('input[name="content_url"]');

    function toggleInputs() {
        if (typeSelect.value === 'image') {
            imageInput.style.display = 'block';
            videoInput.style.display = 'none';
            if (videoUrlInput) videoUrlInput.name = "content_url_disabled";
            if (imageUrlInput) imageUrlInput.name = "content_url";
        } else {
            imageInput.style.display = 'none';
            videoInput.style.display = 'block';
            if (videoUrlInput) videoUrlInput.name = "content_url";
            if (imageUrlInput) imageUrlInput.name = "content_url_disabled";
        }
    }

    typeSelect.addEventListener('change', toggleInputs);
    toggleInputs(); // Initial call
});
</script>

<?php
// Include the new admin footer
include 'includes/admin_footer.php';
?>
