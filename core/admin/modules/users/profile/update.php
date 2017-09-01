<?php
	namespace BigTree;
	
	CSRF::verify();
	
	if ($_POST["password"] && !User::validatePassword($_POST["password"])) {
		$_SESSION["bigtree_admin"]["update_profile"] = $_POST;
		
		Utils::growl("Users", "Invalid Password", "error");
		Router::redirect(ADMIN_ROOT."users/profile/");
	}
	
	User::updateProfile($_POST["name"], $_POST["company"], $_POST["daily_digest"], $_POST["password"]);
	Utils::growl("Users", "Updated Profile");
	Router::redirect(ADMIN_ROOT."dashboard/");
	