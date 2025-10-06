@echo off
echo ========================================
echo OSE E-Docket Demo Data Seeding
echo ========================================
echo.

echo Seeding demo data...
php artisan db:seed --class=DemoDataSeeder

echo.
echo ========================================
echo Demo data seeded!
echo.
echo Demo User Credentials (password: password123):
echo ALU Clerk: sarah.johnson@ose.nm.gov
echo HU Admin: david.thompson@ose.nm.gov
echo Party: john.smith@email.com
echo HU Clerk: lisa.martinez@ose.nm.gov
echo.
echo Sample Cases:
echo WR-2024-001 (Approved - Public)
echo WR-2024-002 (Active - Hearing)
echo WR-2024-003 (Submitted to HU)
echo WR-2024-004 (Draft)
echo ========================================
pause