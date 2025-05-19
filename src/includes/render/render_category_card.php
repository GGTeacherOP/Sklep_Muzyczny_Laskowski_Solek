<?php
  function renderCategoryCard(array $category) : string
  {
    return "
    <a href=\"katalog.php?category_id={$category['id']}\" class=\"instrument-card fade-in\" role=\"button\" tabindex=\"1\">
      <div aria-hidden=\"true\" class=\"instrument-icon\"></div>
      <span class=\"instrument-name\">{$category['nazwa']}</span>
    </a>
    ";
  }

?>