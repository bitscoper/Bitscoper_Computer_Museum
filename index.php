<?php

/* By Abdullah As-Sadeed */

function get_base_url()
{
  $scheme =
    !empty($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] !== "off" ? "https" : "http";
  $base_url = $scheme . "://" . $_SERVER["HTTP_HOST"] . "/";

  return $base_url;
}

function generate_slug($title)
{
  $slug = strtolower($title);
  $slug = preg_replace("/[^a-z0-9]+/", "-", $slug);
  $slug = trim($slug, "-");

  return $slug;
}

function minify_xhtml($xhtml)
{
  $minified_xhtml = preg_replace("/\s+/", " ", $xhtml); // Replace Consecutive Whitespace Characters with " "
  $minified_xhtml = preg_replace("/>\s+</s", "><", $minified_xhtml); # Remove Whitespace Between Tags
  $minified_xhtml = trim($minified_xhtml);

  return $minified_xhtml;
}

function minify_css($css)
{
  $minified_css = preg_replace("/\s+/", " ", $css); // Replace Consecutive Whitespace Characters with " "
  $minified_css = preg_replace("/\s*([{:;},])\s*/", '$1', $minified_css); // Remove Whitespace Around Symbols
  $minified_css = trim($minified_css);

  return $minified_css;
}

$data_directory_path = "Data";
$template_xhtml_file_path = "Template.xhtml";

$javascript_nonce = base64_encode(random_bytes(16));

header("Content-Type: application/xhtml+xml; charset=UTF-8");
header("Content-Security-Policy: script-src 'self' 'nonce-$javascript_nonce';");

$titles = [];
$total_photographs = 0;

foreach (new DirectoryIterator($data_directory_path) as $directory) {
  if (!$directory->isDot() && $directory->isDir()) {
    $title = $directory->getFilename();
    $photographs = [];

    foreach (new DirectoryIterator($directory->getPathname()) as $file) {
      if (!$file->isDot() && $file->isFile()) {
        $extension = strtolower($file->getExtension());

        if (
          in_array(
            $extension,
            ["avif", "gif", "jpeg", "jpg", "png", "webp"],
            true,
          )
        ) {
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

    shuffle($photographs);

    $titles[] = [
      "title" => $title,
      "photographs" => $photographs,
    ];
  } else {
    continue;
  }
}

usort($titles, fn($a, $b) => strcmp($a["title"], $b["title"]));

libxml_use_internal_errors(true);
$dom_document = new DOMDocument("1.0", "UTF-8");
$dom_document->formatOutput = false;
$dom_document->load($template_xhtml_file_path); // Loads XML File
libxml_clear_errors();

$elements = $dom_document->getElementsByTagName("*");
foreach ($elements as $element) {
  if ($element->hasAttribute("id")) {
    $element->setIdAttribute("id", true); # Enables getElementById
  } else {
    continue;
  }
}

$head_element = $dom_document->getElementsByTagName("head")->item(0);
if ($head_element !== null) {
  $base_element = $dom_document->createElement("base");
  $base_element->setAttribute("href", get_base_url());

  $inserted_base_element = false;

  foreach ($head_element->childNodes as $child_node) {
    if (
      $child_node->nodeName === "meta" &&
      $child_node->hasAttribute("charset")
    ) {
      if ($child_node->nextSibling) {
        $head_element->insertBefore($base_element, $child_node->nextSibling);
      } else {
        $head_element->appendChild($base_element);
      }

      $inserted_base_element = true;
      break;
    } else {
      continue;
    }
  }
}

$style_elements = $dom_document->getElementsByTagName("style");
foreach ($style_elements as $style_element) {
  $css = $style_element->nodeValue;

  if ($css !== null && $css !== "") {
    while ($style_element->firstChild) {
      $style_element->removeChild($style_element->firstChild);
    }

    $style_element->appendChild(
      $dom_document->createTextNode(minify_css($css)),
    );
  } else {
    continue;
  }
}

$counts_p_element = $dom_document->getElementById("counts");
if ($counts_p_element !== null) {
  while ($counts_p_element->firstChild !== null) {
    $counts_p_element->removeChild($counts_p_element->firstChild);
  }

  $counts_p_element->appendChild(
    $dom_document->createTextNode(
      $total_photographs . " Photographs of " . count($titles) . " Computers",
    ),
  );
}

$main_element = $dom_document->getElementById("main");

if ($main_element !== null) {
  while ($main_element->firstChild !== null) {
    $main_element->removeChild($main_element->firstChild);
  }

  foreach ($titles as $computer) {
    $section_element = $dom_document->createElement("section");
    $section_element->setAttribute("id", generate_slug($computer["title"]));

    $h2_element = $dom_document->createElement("h2");
    $h2_element->appendChild($dom_document->createTextNode($computer["title"]));
    $section_element->appendChild($h2_element);

    $photographs_div_element = $dom_document->createElement("div");
    $photographs_div_element->setAttribute("class", "photographs");

    foreach ($computer["photographs"] as $photograph) {
      $figure_element = $dom_document->createElement("figure");

      $hyperlink_element = $dom_document->createElement("a");
      $hyperlink_element->setAttribute("href", $photograph["path"]);
      $hyperlink_element->setAttribute("target", "_blank");
      $hyperlink_element->setAttribute("title", $photograph["caption"]);

      $image_element = $dom_document->createElement("img");
      $image_element->setAttribute("src", $photograph["path"]);
      $image_element->setAttribute("loading", "lazy");
      $image_element->setAttribute("alt", $photograph["caption"]);
      $hyperlink_element->appendChild($image_element);

      $figure_element->appendChild($hyperlink_element);

      $figcaption_element = $dom_document->createElement("figcaption");
      $figcaption_element->appendChild(
        $dom_document->createTextNode($photograph["caption"]),
      );

      $figure_element->appendChild($figcaption_element);
      $photographs_div_element->appendChild($figure_element);
    }

    $section_element->appendChild($photographs_div_element);
    $main_element->appendChild($section_element);
  }
}

$script_elements = $dom_document->getElementsByTagName("script");

foreach ($script_elements as $script) {
  $script->setAttribute("nonce", $javascript_nonce);
}

echo minify_xhtml($dom_document->saveXML());

exit();
