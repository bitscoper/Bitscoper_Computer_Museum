<?php
/* By Abdullah As-Sadeed */

$root = "Data";
$allowed_extensions = ["avif", "gif", "jpeg", "jpg", "png", "webp"];

$titles = [];
$total_photographs = 0;

foreach (new DirectoryIterator($root) as $directory) {
  if (!$directory->isDot() && $directory->isDir()) {
    $title = $directory->getFilename();
    $photographs = [];

    foreach (new DirectoryIterator($directory->getPathname()) as $file) {
      if (!$file->isDot() && $file->isFile()) {
        $extension = strtolower($file->getExtension());

        if (in_array($extension, $allowed_extensions)) {
          $photographs[] = [
            "path" => $file->getPathname(),
            "caption" => pathinfo($file->getFilename(), PATHINFO_FILENAME),
          ];

          $total_photographs++;
        } else {
          continue;
        }
      } else {
        continue;
      }
    }

    usort($photographs, fn($a, $b) => strcmp($a["caption"], $b["caption"]));

    $titles[] = [
      "title" => $title,
      "photographs" => $photographs,
    ];
  } else {
    continue;
  }
}

usort($titles, fn($a, $b) => strcmp($a["title"], $b["title"]));
?>
<!DOCTYPE html>
<!-- By Abdullah As-Sadeed -->
<html lang="en-US">
  <head>
    <meta charset="UTF-8">
    <title>Bitscoper Computer Museum</title>
    <meta title="author" content="Abdullah As-Sadeed" />
    <meta title="description" content="Bitscoper Computer Museum" />
    <style>
      * {
        box-sizing: border-box;
      }

      body {
        margin: 0;
        font-family: system-ui, sans-serif;
        background: #f4f4f4;
        color: #222;
        line-height: 1.5;
      }

      header {
        background: #20242a;
        color: white;
        padding: 3rem 1rem;
        text-align: center;
      }

      header h1 {
        margin: 0;
        font-size: 2.5rem;
      }

      header p {
        color: #bbb;
      }

      main {
        max-width: 1500px;
        margin: auto;
        padding: 2rem;
      }

      section {
        margin-bottom: 4rem;
      }

      section h2 {
        border-bottom: 3px solid #ddd;
        padding-bottom: 0.5rem;
        margin-bottom: 1.5rem;
      }

      .gallery {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
        gap: 20px;
      }

      figure {
        margin: 0;
        background: white;
        border-radius: 10px;
        overflow: hidden;
        box-shadow: 0 3px 8px rgba(0, 0, 0, 0.15);
      }

      figure:hover {
        transform: translateY(-2px);
        transition: 0.2s;
      }

      img {
        display: block;
        width: 100%;
        height: 220px;
        object-fit: cover;
      }

      figcaption {
        padding: 0.8rem;
        font-size: 0.95rem;
      }

      a {
        color: inherit;
        text-decoration: none;
      }

      footer {
        text-align: center;
        padding: 2rem;
        color: #666;
      }
    </style>
  </head>
  <body lang="en-US">
    <header>
      <h1>Bitscoper Computer Museum</h1>
      <p>
        <?= $total_photographs ?> Photographs of <?= count($titles) ?> Computers
      </p>
    </header>

    <main>
      <?php foreach ($titles as $computer): ?>
      <section>
        <h2><?= htmlspecialchars($computer["title"]) ?></h2>
        <div class="gallery">
          <?php foreach ($computer["photographs"] as $photograph): ?>
          <figure>
            <a href="<?= htmlspecialchars(
              $photograph["path"],
            ) ?>" target="_blank">
              <img src="<?= htmlspecialchars(
                $photograph["path"],
              ) ?>" loading="lazy" alt="<?= htmlspecialchars(
  $photograph["caption"],
) ?>" />
            </a>

            <figcaption>
              <?= htmlspecialchars($photograph["caption"]) ?>
            </figcaption>
          </figure>
          <?php endforeach; ?>
        </div>
      </section>
      <?php endforeach; ?>
    </main>

    <footer>
      <a href="https://github.com/bitscoper/Bitscoper_Computer_Museum/" title="Source Code">Source Code</a>
    </footer>
  </body>
</html>
