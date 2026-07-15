<?php
session_start();
if (empty($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$slidesJson = __DIR__ . '/ev-slides.json';
if (file_exists($slidesJson)) {
    $slides = json_decode(file_get_contents($slidesJson), true);
    if (!is_array($slides)) $slides = [];
} else {
    $slides = [];
}

$slidesDir = __DIR__ . '/slides';
if (!is_dir($slidesDir)) mkdir($slidesDir, 0755, true);

$messages = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'add') {
        $type = $_POST['type'] ?? 'image';
        $title = trim($_POST['title'] ?? '');
        $caption = trim($_POST['caption'] ?? '');
        $duration = intval($_POST['duration'] ?? 5000);
        $src = '';

        if (in_array($type, ['image', 'video'])) {
            if (!empty($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {
                $tmp = $_FILES['file']['tmp_name'];
                $orig = $_FILES['file']['name'];
                $ext = strtolower(pathinfo($orig, PATHINFO_EXTENSION));
                $allowed = $type === 'image' ? ['jpg','jpeg','png','gif','webp'] : ['mp4','webm','ogg'];
                if (!in_array($ext, $allowed)) {
                    $messages[] = 'Upload failed: invalid file type.';
                } else {
                    $basename = uniqid('slide_', true) . '.' . $ext;
                    $destRel = 'slides/' . $basename;
                    $dest = __DIR__ . '/' . $destRel;
                    if (move_uploaded_file($tmp, $dest)) {
                        $src = $destRel;
                    } else {
                        $messages[] = 'Upload failed moving file.';
                    }
                }
            } elseif (!empty($_POST['remote_src'])) {
                $remote = trim($_POST['remote_src']);
                if (filter_var($remote, FILTER_VALIDATE_URL)) $src = $remote;
                else $messages[] = 'Invalid remote URL.';
            } else {
                $messages[] = 'No file uploaded.';
            }
        }

        if (empty($messages)) {
            $new = ['type'=>$type, 'title'=>$title, 'caption'=>$caption, 'duration'=>$duration];
            if ($src) $new['src'] = $src;
            $slides[] = $new;
            file_put_contents($slidesJson, json_encode($slides, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES));
            header('Location: ev-slides-admin.php?success=1');
            exit;
        }
    } elseif ($action === 'move') {
        $index = intval($_POST['index']);
        $dir = $_POST['dir'] ?? 'up';
        if (isset($slides[$index])) {
            $target = $dir === 'up' ? $index-1 : $index+1;
            if (isset($slides[$target])) {
                $tmp = $slides[$target];
                $slides[$target] = $slides[$index];
                $slides[$index] = $tmp;
                file_put_contents($slidesJson, json_encode($slides, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES));
            }
        }
        header('Location: ev-slides-admin.php');
        exit;
    } elseif ($action === 'delete') {
        $index = intval($_POST['index']);
        if (isset($slides[$index])) {
            $s = $slides[$index];
            if (!empty($s['src']) && strpos($s['src'], 'slides/') === 0) {
                $p = __DIR__ . '/' . $s['src'];
                if (file_exists($p)) @unlink($p);
            }
            array_splice($slides, $index, 1);
            file_put_contents($slidesJson, json_encode($slides, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES));
        }
        header('Location: ev-slides-admin.php');
        exit;
    } elseif ($action === 'save_json') {
        $json = $_POST['slides_json'] ?? '[]';
        $decoded = json_decode($json, true);
        if (is_array($decoded)) {
            file_put_contents($slidesJson, json_encode($decoded, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES));
            header('Location: ev-slides-admin.php?success=1');
            exit;
        } else {
            $messages[] = 'Invalid JSON.';
        }
    }
}
?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Slides Admin — EV LGRRC</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-50 text-slate-900">
  <div class="max-w-5xl mx-auto p-6">
    <div class="flex items-center justify-between mb-6">
      <h1 class="text-2xl font-semibold">Slideshow Admin</h1>
      <div>
        <a href="ev-lgrrc.php" class="inline-block bg-[#8b0b09] text-white px-3 py-2 rounded">View Home</a>
      </div>
    </div>

    <?php if (!empty($_GET['success'])): ?>
      <div class="mb-4 p-3 bg-green-50 border border-green-200 text-green-800">Saved.</div>
    <?php endif; ?>
    <?php foreach ($messages as $m): ?>
      <div class="mb-4 p-3 bg-yellow-50 border border-yellow-200 text-yellow-800"><?= htmlspecialchars($m) ?></div>
    <?php endforeach; ?>

    <section class="mb-8">
      <h2 class="text-lg font-medium mb-2">Add new slide</h2>
      <form method="post" enctype="multipart/form-data" class="grid grid-cols-1 gap-3 md:grid-cols-2">
        <input type="hidden" name="action" value="add">
        <label class="block"><span class="text-sm">Type</span>
          <select name="type" id="newSlideType" class="mt-1 block w-full">
            <option value="image">Image</option>
            <option value="video">Video</option>
            <option value="text">Text</option>
          </select>
        </label>
        <label class="block"><span class="text-sm">Title</span>
          <input name="title" class="mt-1 block w-full border rounded px-2 py-2">
        </label>
        <label class="block md:col-span-2"><span class="text-sm">Caption</span>
          <textarea name="caption" class="mt-1 block w-full border rounded px-2 py-2"></textarea>
        </label>
        <label class="block"><span class="text-sm">Duration (ms)</span>
          <input name="duration" type="number" value="5000" class="mt-1 block w-full border rounded px-2 py-2">
        </label>
        <label id="fileLabel" class="block"><span class="text-sm">File (image/video)</span>
          <input name="file" type="file" class="mt-1 block w-full">
        </label>
        <label id="remoteLabel" class="block"><span class="text-sm">Remote URL (optional)</span>
          <input name="remote_src" type="url" class="mt-1 block w-full border rounded px-2 py-2">
        </label>
        <div class="md:col-span-2">
          <button class="bg-[#8b0b09] text-white px-4 py-2 rounded">Add Slide</button>
        </div>
      </form>
    </section>

    <section class="mb-8">
      <h2 class="text-lg font-medium mb-2">Existing slides</h2>
      <?php if (empty($slides)): ?>
        <div class="p-3 bg-white border rounded">No slides yet.</div>
      <?php else: ?>
        <div class="space-y-4">
          <?php foreach ($slides as $i => $s): ?>
            <div class="p-3 bg-white border rounded flex gap-4 items-start">
              <div class="w-48 flex-shrink-0">
                <?php if (!empty($s['type']) && $s['type'] === 'image' && !empty($s['src'])): ?>
                  <img src="<?= htmlspecialchars($s['src']) ?>" alt="<?= htmlspecialchars($s['title'] ?? '') ?>" class="w-full h-28 object-cover rounded">
                <?php elseif (!empty($s['type']) && $s['type'] === 'video' && !empty($s['src'])): ?>
                  <video src="<?= htmlspecialchars($s['src']) ?>" poster="<?= htmlspecialchars($s['poster'] ?? '') ?>" class="w-full h-28 object-cover rounded" muted playsinline controls></video>
                <?php else: ?>
                  <div class="h-28 flex items-center justify-center bg-slate-50 rounded text-sm text-slate-600">Text slide</div>
                <?php endif; ?>
              </div>
              <div class="flex-1">
                <div class="flex items-start justify-between gap-3">
                  <div>
                    <div class="text-sm text-slate-600">Type: <?= htmlspecialchars($s['type'] ?? 'text') ?> • Duration: <?= intval($s['duration'] ?? 5000) ?>ms</div>
                    <h3 class="font-semibold text-slate-900"><?= htmlspecialchars($s['title'] ?? '') ?></h3>
                    <p class="text-sm text-slate-600"><?= htmlspecialchars($s['caption'] ?? '') ?></p>
                  </div>
                  <div class="flex flex-col gap-2">
                    <form method="post" style="display:inline">
                      <input type="hidden" name="action" value="move">
                      <input type="hidden" name="index" value="<?= $i ?>">
                      <input type="hidden" name="dir" value="up">
                      <button class="px-2 py-1 border rounded text-sm">Up</button>
                    </form>
                    <form method="post" style="display:inline">
                      <input type="hidden" name="action" value="move">
                      <input type="hidden" name="index" value="<?= $i ?>">
                      <input type="hidden" name="dir" value="down">
                      <button class="px-2 py-1 border rounded text-sm">Down</button>
                    </form>
                    <form method="post" style="display:inline" onsubmit="return confirm('Delete this slide?');">
                      <input type="hidden" name="action" value="delete">
                      <input type="hidden" name="index" value="<?= $i ?>">
                      <button class="px-2 py-1 bg-red-600 text-white rounded text-sm">Delete</button>
                    </form>
                  </div>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </section>

    <section>
      <h2 class="text-lg font-medium mb-2">Edit raw JSON</h2>
      <form method="post">
        <input type="hidden" name="action" value="save_json">
        <textarea name="slides_json" class="w-full h-64 p-2 border rounded font-mono text-sm"><?= htmlspecialchars(json_encode($slides, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES)) ?></textarea>
        <div class="mt-2"><button class="bg-[#8b0b09] text-white px-4 py-2 rounded">Save JSON</button></div>
      </form>
    </section>
  </div>
  <script>
    // Toggle file/remote input depending on type
    const typeEl = document.getElementById('newSlideType');
    const fileLabel = document.getElementById('fileLabel');
    const remoteLabel = document.getElementById('remoteLabel');
    function toggleInputs(){
      const v = typeEl.value;
      if (v === 'text') { fileLabel.style.display = 'none'; remoteLabel.style.display = 'none'; }
      else { fileLabel.style.display = 'block'; remoteLabel.style.display = 'block'; }
    }
    if (typeEl) { typeEl.addEventListener('change', toggleInputs); toggleInputs(); }
  </script>
</body>
</html>
