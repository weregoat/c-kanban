## How to deploy the application

Assuming the starting point is the `tar` archive this file will be packed in, all you need to do, ideally, would be to explode it into the directory you want to use as root for the project and then symlink the `src/public` subdirectory as the document root of the website (or a subdir).

The application uses [Composer](https://getcomposer.org/) for the dependencies and to create the _autoload_ file used by the application; you need to install it (manually or through a packaging system) and from the directory with the code run:

```
composer install --no-dev
```

Other options are possible, YMMV.
If all goes well it will create a `vendor` directory with the dependencies and the PSR-4 _autoload_ file.
If it doesn't, you will need to check with the problems composer may had outlined (wrong PHP version or missing components...). They are mostly related to phpunit requirements (sadly the --no-dev option doesn't seem to stop Composer from checking them); removing the phpunit dependency from `composer.json` may help.

The application **requires** the following environment variables to run:
* `GH_CLIENT_ID`: the client ID of the OAuth Application for authorising GitHub API access.
* `GH_CLIENT_SECRET`: the client secret of the OAuth Application.
* `GH_REPOSITORIES`: a list of repositories to get the milestones for separated by `|`.
* `GH_ACCOUNT`: the name of the account the repositories from.

**Optionally** the following environment variables can be set:
* `GH_LABELS`: a list of labels that will mark the issue as (paused) when matches (case insensitive), separated by `|`; defaults to empty list.
* `GH_SCOPE`: the scope of the authorisation from GitHub (defaults to `repo`).

To get the client ID and secret, you need to [create an OAuth App](https://developer.github.com/apps/building-oauth-apps/creating-an-oauth-app/). 

How to pass the environment variables to the web application and how to publish it depends on the particular setting they will run on.
For example, with _Apache_, it may be a virtual host configuration like:
```
<VirtualHost *:80>

    # Points to the index.php in the public directory. 
    # Or symlink to, or whatever.
    DocumentRoot /var/www/kanban/src/public/
    
	[...]
	SetEnv GH_CLIENT_ID [your_app_client_id]
	SetEnv GH_CLIENT_SECRET [your_app_client_secret]
	SetEnv GH_ACCOUNT "microsoft"
	SetEnv GH_REPOSITORIES "vscode|msbuild"
	SetEnv GH_SCOPE "repo:status"
	SetEnv GH_LABELS "xplat|bug"

</VirtualHost>
```

Or an `.htaccess` file. YMMV

You can also use the built-in web server; assuming the code was exploded in the `/var/www/kanban/` directory as above something like:
```
cd /var/www/kanban/src/public/
export GH_CLIENT_ID=[your_app_client_id]
export GH_CLIENT_SECRET=[your_app_client_secret]
export GH_ACCOUNT=microsoft
export GH_REPOSITORIES='vscode|msbuild'
export GH_SCOPE=''
export GH_LABELS='xplat|bug'
php -S localhost:9090
```

# How to test the application

There is a **very basic** PHPUnit suit to check some of the components (I don't follow TDD); run composer **without** the `--no-dev` option and it will install also `phpunit` binaries and dependencies.
Then run the suite:
```
./vendor/bin/phpunit -c ./phpunit.xml
```

The command line scrip in the `bin` directory can be used to quickly check repositories. It uses a personal token instead of OAuth App authentication to access the GitHub API.
List of options with:
```
./bin/milestones.php --help
```

For the web application... beats me.
Once the web-page is up and running and the environment variables are properly set the page, on first access, should redirect to GitHub for authorising the App to access the repositories of the `GH_ACCOUNT`.
Failures should result in an error message and a 500 status, plus logs according to `error_log` setting.
If a repository has no milestones, the list will be just empty.

I suppose a script can be written to check the results for a given repository, but I did not write any.