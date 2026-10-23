@servers(['web' => $_ENV['PROD_SSH_HOST']])

@setup
    $app_dir = '/var/www/app-rt04/site';
@endsetup

@story('deploy')
    down_website
    pull_repo
    run_composer
    migrate
    up_website
@endstory

@task('down_website')
    echo "Set site down"
    cd {{ $app_dir }}
    php artisan down
@endtask

@task('pull_repo')
    echo "Pull master repo"
    cd {{ $app_dir }}
    git pull origin master 
@endtask 

@task('run_composer')
    echo "Composer install"
    cd {{ $app_dir }}
    composer install --prefer-dist
@endtask

@task('migrate')
    echo "Migrate Database"
    cd {{ $app_dir }}
    php artisan migrate
@endtask

@task('up_website')
    echo "Set site up"
    cd {{ $app_dir }}
    php artisan up
@endtask
