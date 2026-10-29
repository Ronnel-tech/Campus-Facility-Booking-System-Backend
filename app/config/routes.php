<?php
defined('PREVENT_DIRECT_ACCESS') OR exit('No direct script access allowed');
/**
 * ------------------------------------------------------------------
 * LavaLust - an opensource lightweight PHP MVC Framework
 * ------------------------------------------------------------------
 *
 * MIT License
 *
 * Copyright (c) 2020 Ronald M. Marasigan
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 *
 * @package LavaLust
 * @author Ronald M. Marasigan <ronald.marasigan@yahoo.com>
 * @since Version 1
 * @link https://github.com/ronmarasigan/LavaLust
 * @license https://opensource.org/licenses/MIT MIT License
 */

/*
| -------------------------------------------------------------------
| URI ROUTING
| -------------------------------------------------------------------
| Here is where you can register web routes for your application.
|
|
*/
/** @var object $router **/

$router->get('/', 'Welcome::index');

$router->get('create-migration/{migration_class}', 'MigrationController::create_migration');
$router->get('create-migration/{migration_class}/{type}', 'MigrationController::create_migration');

$router->get('migrate/dry-run', 'MigrationController::migrate_dry');
$router->get('migrate', 'MigrationController::migrate');
$router->get('migrate-to/{version}', 'MigrationController::migrate_to');

$router->get('rollback', 'MigrationController::rollback');
$router->get('rollback/{steps}', 'MigrationController::rollback');
$router->get('rollback-to/{version}', 'MigrationController::rollback_to');
$router->get('rollback-all', 'MigrationController::rollback_all');

$router->get('refresh', 'MigrationController::refresh');
$router->get('reset', 'MigrationController::reset');
$router->get('fresh', 'MigrationController::fresh');

$router->get('status', 'MigrationController::status');
$router->get('preview', 'MigrationController::preview');

// ============================================================================
// API ROUTES - Campus Facility Booking System
// ============================================================================

// Authentication Routes
$router->post('api/auth/register', 'AuthController::register');
$router->post('api/auth/login', 'AuthController::login');
$router->post('api/auth/logout', 'AuthController::logout');
$router->post('api/auth/refresh', 'AuthController::refreshToken');

// Public Facilities Routes
$router->get('api/facilities', 'FacilitiesController::index');
$router->get('api/facilities/{id}', 'FacilitiesController::show');
$router->get('api/facilities/{id}/availability', 'FacilitiesController::getAvailability');
$router->get('api/facilities/categories', 'FacilitiesController::categories');

// Admin Facilities Management
$router->post('api/admin/facilities', 'FacilitiesController::store');
$router->put('api/admin/facilities/{id}', 'FacilitiesController::update');
$router->delete('api/admin/facilities/{id}', 'FacilitiesController::delete');

// Public Assets Routes
$router->get('api/assets', 'AssetsController::index');
$router->get('api/assets/{id}', 'AssetsController::show');
$router->get('api/assets/categories', 'AssetsController::categories');

// Admin Assets Management
$router->post('api/admin/assets', 'AssetsController::store');
$router->put('api/admin/assets/{id}', 'AssetsController::update');
$router->delete('api/admin/assets/{id}', 'AssetsController::delete');
$router->get('api/admin/assets/inventory', 'AssetsController::inventory');

// User Reservations Routes
$router->get('api/reservations', 'ReservationsController::index');
$router->get('api/reservations/{id}', 'ReservationsController::show');
$router->post('api/reservations', 'ReservationsController::store');
$router->post('api/reservations/{id}/cancel', 'ReservationsController::cancel');

// Admin Reservations Management
$router->get('api/admin/reservations', 'ReservationsController::allReservations');
$router->post('api/admin/reservations/{id}/approve', 'ReservationsController::approve');
$router->post('api/admin/reservations/{id}/reject', 'ReservationsController::reject');

// User Profile Routes
$router->get('api/users/profile', 'UsersController::getProfile');
$router->put('api/users/profile', 'UsersController::updateProfile');
$router->post('api/users/change-password', 'UsersController::changePassword');

// Notification Preferences
$router->get('api/users/notification-preferences', 'UsersController::getNotificationPreferences');
$router->put('api/users/notification-preferences', 'UsersController::updateNotificationPreferences');

// User Notifications Routes
$router->get('api/notifications', 'NotificationsController::index');
$router->post('api/notifications/{id}/read', 'NotificationsController::markAsRead');
$router->post('api/notifications/read-all', 'NotificationsController::markAllAsRead');
$router->delete('api/notifications/{id}', 'NotificationsController::delete');
$router->get('api/notifications/unread-count', 'NotificationsController::getUnreadCount');

// Admin Dashboard
$router->get('api/admin/dashboard', 'AdminController::dashboard');
$router->get('api/admin/reports/recent-activity', 'AdminController::recentActivity');

// Admin User Management
$router->get('api/admin/users', 'AdminController::getUsers');
$router->post('api/admin/users', 'AdminController::createUser');
$router->post('api/admin/users/{id}/suspend', 'AdminController::suspendUser');
$router->post('api/admin/users/{id}/unsuspend', 'AdminController::unsuspendUser');

// Maintenance Records
$router->get('api/admin/maintenance', 'MaintenanceController::index');
$router->get('api/admin/maintenance/{id}', 'MaintenanceController::show');
$router->post('api/admin/maintenance', 'MaintenanceController::store');
$router->put('api/admin/maintenance/{id}', 'MaintenanceController::update');
$router->delete('api/admin/maintenance/{id}', 'MaintenanceController::delete');

// Reports Routes
$router->get('api/admin/reports/facility-usage', 'ReportsController::facilityUsage');
$router->get('api/admin/reports/asset-borrowing', 'ReportsController::assetBorrowing');
$router->get('api/admin/reports/reservation-stats', 'ReportsController::reservationStats');
$router->get('api/admin/reports/overdue-returns', 'ReportsController::overdueReturns');
$router->get('api/admin/reports/maintenance-log', 'ReportsController::maintenanceLog');
$router->get('api/admin/reports/cancellations', 'ReportsController::cancellationReport');
$router->post('api/admin/reports/export', 'ReportsController::exportReport');