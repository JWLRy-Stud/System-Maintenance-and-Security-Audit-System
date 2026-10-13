@props(['class' => 'nav-item'])
<button type="button" class="{{ $class }}" data-theme-toggle>
  <span class="theme-icon-light"><x-icon name="sun" /></span><span class="theme-icon-dark"><x-icon name="moon" /></span>
  <span class="theme-label-light">Switch to light mode</span><span class="theme-label-dark">Switch to dark mode</span>
</button>
