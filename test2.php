<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Student Scheme of Work</title>
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

<body>
    <?php include("../includes/sidebar.php"); ?>
    <main class="main-content">
        <?php include("../includes/headernav.php"); ?>

        <div class="container py-4">
            <h4 class="text-center text-primary mb-4">Check Scheme of Work</h4>

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
                <button type="submit" class="btn btn-primary">Fetch Scheme of Work</button>
            </form>

            <?php if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($schemeOfWork)): ?>
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
                            <?php foreach ($schemeOfWork as $index => $work): ?>
                                <tr>
                                    <td><?= $index + 1; ?></td>
                                    <td><?= htmlspecialchars($work['scheme_title']); ?></td>
                                    <td><?= htmlspecialchars($work['subject_name']); ?></td>
                                    <td><?= htmlspecialchars($work['class_name']); ?></td>
                                    <td><?= htmlspecialchars($work['arm_name']); ?></td>
                                    <td>
                                        <?php if (!empty($work['attachment_path'])): ?>
                                            <a href="<?= htmlspecialchars($work['attachment_path']); ?>" download>Download</a>
                                        <?php else: ?>
                                            N/A
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#viewModal<?= $work['id'] ?>">View</button>
                                    </td>
                                </tr>

                                <!-- Modal for Viewing Details -->
                                <div class="modal fade" id="viewModal<?= $work['id'] ?>" tabindex="-1" aria-labelledby="viewModalLabel<?= $work['id'] ?>" aria-hidden="true">
                                    <div class="modal-dialog modal-lg">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title" id="viewModalLabel<?= $work['id'] ?>">Scheme of Work Details</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                            </div>
                                            <div class="modal-body">
                                                <h5>Title: <?= htmlspecialchars($work['scheme_title']); ?></h5>
                                                <p><strong>Subject:</strong> <?= htmlspecialchars($work['subject_name']); ?></p>
                                                <p><strong>Class:</strong> <?= htmlspecialchars($work['class_name']); ?> - <?= htmlspecialchars($work['arm_name']); ?></p>
                                                <p><strong>Content:</strong></p>
                                                <div class="mb-3">
                                                    <textarea class="form-control" id="workContent" name="workContent" rows="4" required><?php echo nl2br(htmlspecialchars($work['scheme_content'])); ?></textarea>
                                                </div>
                                                <?php if (!empty($work['attachment_path'])): ?>
                                                    <p><strong>Attachment:</strong> <a href="<?= htmlspecialchars($work['attachment_path']); ?>" download>Download</a></p>
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
                <p class="mt-4 text-primary">No scheme of work found for the selected subject.</p>
            <?php endif; ?>
        </div>

        <footer style="margin-top: 80px;">
            <?php include("../includes/footer.php") ?>
        </footer>
    </main>
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

        initializeTinyMCE('#workContent', 240);
    </script>
    <?php include("../includes/script.php"); ?>
</body>

</html>