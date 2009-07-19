<div class="w3-user-flash-sidebar2-summary-wrapper">
<?php foreach($success as $userFlash): ?>
  <div class="w3-sidebar2-item<?php echo MLayout::getNumberOfItemsSidebar2()?'':' first'; ?>">
    <div class="w3-user-flash-sidebar2-summary ui-widget ui-state-highlight ui-corner-all">
        <span class="w3-icon-left ui-icon ui-icon-check"></span>
        <?php echo $userFlash; ?> 
    </div>
  </div><!-- w3-sidebar2-item -->
<?php MLayout::incrementNumberOfItemsSidebar2(); ?>
<?php endforeach; ?>
<?php foreach($info as $userFlash): ?>
  <div class="w3-sidebar2-item<?php echo MLayout::getNumberOfItemsSidebar2()?'':' first'; ?>">
    <div class="w3-user-flash-sidebar2-summary ui-widget ui-state-highlight ui-corner-all">
        <span class="w3-icon-left ui-icon ui-icon-info"></span>
        <?php echo $userFlash; ?> 
    </div>
  </div><!-- w3-sidebar2-item -->
<?php MLayout::incrementNumberOfItemsSidebar2(); ?>
<?php endforeach; ?>
<?php foreach($error as $userFlash): ?>
  <div class="w3-sidebar2-item<?php echo MLayout::getNumberOfItemsSidebar2()?'':' first'; ?>">
    <div class="w3-user-flash-sidebar2-summary ui-widget ui-state-error ui-corner-all">
        <span class="w3-icon-left ui-icon ui-icon-alert"></span>
        <?php echo $userFlash; ?> 
    </div>
  </div><!-- w3-sidebar2-item -->
<?php MLayout::incrementNumberOfItemsSidebar2(); ?>
<?php endforeach; ?>
</div><!-- w3-user-flash-sidebar2-summary-wrapper -->
