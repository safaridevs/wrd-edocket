@echo off
echo ========================================
echo OSE E-Docket Demo Users Setup
echo ========================================
echo.

echo Creating demo users...
php artisan db:seed --class=SimpleDemoSeeder

echo.
echo ========================================
echo Demo users created!
echo.
echo Demo User Credentials (password: password123):
echo ALU Clerk: sarah.johnson@ose.nm.gov
echo ALU Manager: michael.rodriguez@ose.nm.gov
echo ALU Attorney: jennifer.chen@ose.nm.gov
echo HU Admin: david.thompson@ose.nm.gov
echo HU Clerk: lisa.martinez@ose.nm.gov
echo Hydrology Expert: robert.wilson@ose.nm.gov
echo Party 1: john.smith@email.com
echo Party 2: maria.garcia@email.com
echo Party 3: contact@abcranch.com
echo ========================================
pause