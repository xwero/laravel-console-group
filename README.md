# laravel console group

At the moment it is POC code. A lot of functionality is missing.

Just download the code and add the Group folder to your laravel app/Console/Commands folder.

then you can use `artisan make:group`.

## What does it do?

Instead of placing controller, model and repository in the common folders. This file generator class places them in a single child folder of app/Groups.

## Why?

I'm losing time closing and opening folders once the project is getting to completion. 

I also don't like that the folder structure isn't flat. More folder clicking, ugh.

## Do you want to add all the files in one folder?

No. The common folders are good for shared classes/interfaces.

