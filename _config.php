<?php

use SilverStripe\Admin\CMSMenu;
use SilverStripe\SecurityExtensions\Control\SudoModeController;

CMSMenu::remove_menu_class(SudoModeController::class);
