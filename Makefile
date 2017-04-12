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
	chmod 777 sites/default -R; \
	mkdir -p sites/default/files; \
	mkdir -p sites/default/files/tmp; \
	drush si custom_standard --db-url="mysql://$(DB_USER):$(DB_PASS)@localhost/$(DB_NAME)" --account-pass=admin -y --site-name="$(SITE_NAME)" --config-dir=sites/default/config; \
	drush cim -y; \
	drush en custom_default_content  -y; \
	drush php-eval "\Drupal::service('default_content.manager')->importContent('custom_default_content');" \

dev:
	sudo chmod -R 777 sites/default; \
	cp sites/default/default.services.yml sites/default/services.yml; \
	cp sites/example.settings.local.php sites/default/settings.local.php; \
	drush en devel devel_generate kint -y; \
	drush pm-uninstall dynamic_page_cache internal_page_cache -y; \
	drush cr; \

dis-dev:
	drush pm-uninstall devel devel_generate kint -y; \
	drush en dynamic_page_cache internal_page_cache -y; \
	drush cr;

export-conf:
	DIR_DEFAULT_CONTENT="modules/custom/custom_default_content/content"; \
	rm -rf modules/custom/custom_default_content/content; \
	drush cex -y; \
	drush dcer block_content --folder=modules/custom/custom_default_content/content; \
	drush dcer node --folder=modules/custom/custom_default_content/content; \
	drush dcer taxonomy_term --folder=modules/custom/custom_default_content/content; \
	drush dcer menu_link_content --folder=modules/custom/custom_default_content/content; \
	rm -rf modules/custom/custom_default_content/content/user/; \