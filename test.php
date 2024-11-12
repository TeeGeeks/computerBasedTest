<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <link rel="apple-touch-icon" sizes="76x76" href="<?php echo BASE_URL; ?>assets/img/apple-icon.png" />
    <link rel="icon" type="image/png" href="<?php echo BASE_URL; ?>assets/img/favicon.png" />
    <title>Lesson Notes</title>
    <!-- Fonts and icons -->
    <!-- <link rel="stylesheet" type="text/css" href="https://fonts.googleapis.com/css?family=Roboto:300,400,500,700,900|Roboto+Slab:400,700" /> -->
    <!-- Nucleo Icons -->
    <link href="<?php echo BASE_URL; ?>assets/css/nucleo-icons.css" rel="stylesheet" />
    <link href="<?php echo BASE_URL; ?>assets/css/nucleo-svg.css" rel="stylesheet" />
    <!-- Font Awesome Icons -->
    <script src="https://kit.fontawesome.com/42d5adcbca.js" crossorigin="anonymous"></script>
    <script src="<?php echo BASE_URL; ?>assets/js/core/fontawsomeicon.js" crossorigin="anonymous"></script>
    <!-- Material Icons -->
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/material-icons.css" />
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons+Round" rel="stylesheet" />
    <!-- <link href="https://fonts.googleapis.com/icon?family=Material+Icons+Round" rel="stylesheet" /> -->
    <!-- CSS Files -->
    <link id="pagestyle" href="<?php echo BASE_URL; ?>assets/css/material-dashboard.css?v=3.1.0" rel="stylesheet" />

    <link rel="stylesheet" type="text/css" href="<?php echo BASE_URL; ?>assets/css/mycss.css">
    <!-- Place the first <script> tag in your HTML's <head> -->
    <script src="<?php echo BASE_URL; ?>assets/js/tinymce/tinymce/tinymce.min.js">
    </script>
    <!-- <script src="https://cdn.tiny.cloud/1/k014ifj5itll2amihajv2u2mwio7wbnpg1g0yg2fmr1f3m8u/tinymce/7/tinymce.min.js"
        referrerpolicy="origin"></script> -->

    <!-- <link href="https://fonts.googleapis.com/css2?family=STIX+Two+Math&display=swap" rel="stylesheet"> -->

    <style>
        .form-control,
        .form-select {
            border: 1px solid #ced4da;
            border-radius: 0.375rem;
            padding: 0.375rem 0.75rem;
        }

        .d-grid .btn {
            border-radius: 0.375rem;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        th,
        td {
            padding: 12px;
            text-align: left;
        }

        th {
            background-color: #f2f2f2;
        }
    </style>
</head>

<body class="g-sidenav-show bg-gray-200">
    <?php include("../includes/sidebar.php"); ?>
    <main class="main-content position-relative max-height-vh-100 h-100 border-radius-lg">
        <?php include("../includes/headernav.php"); ?>
        <div class="container py-4">
            <h4 class="text-center text-primary mb-4">Check Lesson Notes</h4>

            <form action="" method="POST">
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="subject" class="form-label">Subject</label>
                        <select class="form-select" id="subject" name="subject" required>
                            <option value="" disabled selected>Select a subject</option>
                            <?php foreach ($subjects as $subject): ?>
                                <option value="<?= htmlspecialchars($subject['id']) ?>"><?= htmlspecialchars($subject['subject_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary">Fetch Lesson Notes</button>
            </form>

            <?php if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($lessonNotes)): ?>
                <div class="table-responsive">
                    <table id="assignmentsTable" class="table table-striped table-bordered">
                        <thead class="thead-dark">
                            <tr>
                                <th>S/N</th>
                                <th>Title</th>
                                <th>Subject</th>
                                <th>Class</th>
                                <th>Arm</th>
                                <th>Attachment</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($lessonNotes as $index => $note): ?>
                                <tr>
                                    <td><?= $index + 1; ?></td>
                                    <td><?= htmlspecialchars($note['note_title']); ?></td>
                                    <td><?= htmlspecialchars($note['subject_name']); ?></td>
                                    <td><?= htmlspecialchars($note['class_name']); ?></td>
                                    <td><?= htmlspecialchars($note['arm_name']); ?></td>
                                    <td>
                                        <?php if (!empty($note['attachment_path'])): ?>
                                            <a href="<?= htmlspecialchars($note['attachment_path']); ?>" download>Download</a>
                                        <?php else: ?>
                                            N/A
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#viewModal<?= $note['id'] ?>">View</button>
                                    </td>
                                </tr>

                                <!-- Modal for Viewing Details -->
                                <div class="modal fade" id="viewModal<?= $note['id'] ?>" tabindex="-1" aria-labelledby="viewModalLabel<?= $note['id'] ?>" aria-hidden="true">
                                    <div class="modal-dialog modal-lg">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title" id="viewModalLabel<?= $note['id'] ?>">Lesson Note Details</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                            </div>
                                            <div class="modal-body">
                                                <h5>Title: <?= htmlspecialchars($note['note_title']); ?></h5>
                                                <p><strong>Subject:</strong> <?= htmlspecialchars($note['subject_name']); ?></p>
                                                <p><strong>Class:</strong> <?= htmlspecialchars($note['class_name']); ?> - <?= htmlspecialchars($note['arm_name']); ?></p>
                                                <p><strong>Content:</strong></p>
                                                <div class="mb-3">
                                                    <textarea class="form-control" id="noteContent" name="noteContent" rows="4" required><?php echo nl2br(htmlspecialchars($note['note_content'])); ?></textarea>

                                                </div>
                                                <?php if (!empty($note['attachment_path'])): ?>
                                                    <p><strong>Attachment:</strong> <a href="<?= htmlspecialchars($note['attachment_path']); ?>" download>Download</a></p>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php elseif ($_SERVER['REQUEST_METHOD'] === 'POST'): ?>
                <p class="mt-4 text-primary">No lesson notes found for the selected subject.</p>
            <?php endif; ?>
        </div>
    </main>
    <?php include("../includes/script.php"); ?>

    <script>
        function initializeTinyMCE(selector, height) {
            tinymce.init({
                selector: selector,
                height: height,
                plugins: 'advlist autolink lists link image media table charmap paste fullscreen code',
                toolbar: 'undo redo | bold italic underline | bullist numlist | link image media | table charmap | fullscreen code',
                paste_data_images: true, // Allows pasting images from clipboard
                images_upload_url: 'upload_handler.php', // Placeholder for image upload functionality
                automatic_uploads: true, // Enables automatic uploads of media
                file_picker_types: 'image media', // Allows image and media file picking
                setup: function(editor) {
                    editor.on('change', function() {
                        editor.save(); // Auto-save content when it's changed
                    });
                }
            });
        }

        initializeTinyMCE('#noteContent', 240);
    </script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const currentPage = 'Student Lesson Notes';
            updateBreadcrumbs(['Pages', currentPage]);
        });
    </script>
</body>

</html>