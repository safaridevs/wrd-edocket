@echo off
echo ========================================
echo OSE E-Docket Demo Setup
echo ========================================
echo.

echo Running demo setup...
php artisan demo:setup

echo.
echo ========================================
echo Demo setup complete!
echo.
echo You can now:
echo 1. Visit the welcome page (public cases)
echo 2. Login with any of the demo accounts
echo 3. Test different user role workflows
echo.
echo All passwords are: password123
echo ========================================
pause