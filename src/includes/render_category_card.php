<?php
  function renderCategoryCard($category)
  {
    return "
    <div aria-label=\"Wybierz typ {$category['nazwa']}\" class=\"instrument-card fade-in\" role=\"button\" tabindex=\"1\">
      <div aria-hidden=\"true\" class=\"instrument-icon\"></div>
      <span class=\"instrument-name\">{$category['nazwa']}</span>
    </div>
    ";
  }

?>