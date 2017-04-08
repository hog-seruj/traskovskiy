# Read project name from .env file
$(shell cp -n \.env.default \.env)
include .env

install:
	cp src/drush_make/*.make.yml ./; \
	drush make profile.make.yml --concurrency=4 --prepare-install --overwrite -y; \
	cp src/custom_standard profiles/ -R; \
	cp src/config ./sites/default -R; \
	mkdir contrib; \
	mv modules/* contrib; \
	mv contrib modules; \
	mkdir modules/custom; \
	cp src/custom_default_content modules/custom/ -R; \
	make -s si; \

si:
	drush si custom_standard --db-url="mysql://$(DB_USER):$(DB_PASS)@localhost/$(DB_NAME)" --account-pass=admin -y --site-name="$(SITE_NAME)" --config-dir=sites/default/config; \
	drush en $(MODULES) -y; \
	drush config-set system.site default_langcode uk -y; \
	drush en custom_default_content  -y; \
	drush php-eval "\Drupal::service('default_content.manager')->importContent('custom_default_content');"

dev:
	@echo "Enabling Kint..."
	chmod -R 777 build/sites/default/files; \
	chmod -R 777 sites/default/files; \
	cp sites/default/default.services.yml sites/default/services.yml; \
	cp sites/example.settings.local.php sites/default/settings.local.php; \
	drush en devel devel_generate kint -y; \
	drush pm-uninstall dynamic_page_cache internal_page_cache -y; \
	drush cr; \