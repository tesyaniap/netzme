# TikcektBus Backend API

Laravel 12 based Bus Ticketing System API with role-based access control.

## Repository
- **Original**: https://github.com/arifinmn11/laravel-12-init.git
- **My Version**: https://github.com/lexwan/laravel-12-own.git

## API Documentation

Base URL: `/api/v1`

### Authentication
| Method | Endpoint | Description | Access |
|--------|----------|-------------|---------|
| POST | `/auth/login` | User login | Public |
| POST | `/auth/logout` | User logout | Authenticated |
| POST | `/auth/refresh` | Refresh token | Authenticated |
| GET | `/auth/me` | Get user profile | Authenticated |
| GET | `/auth/permissions` | Get user permissions | Authenticated |

### Dashboard
| Method | Endpoint | Description | Access |
|--------|----------|-------------|---------|
| GET | `/dashboard/admin` | Admin dashboard | Admin |
| GET | `/dashboard/mitra` | Partner dashboard | Partner |

### User Management
| Method | Endpoint | Description | Access |
|--------|----------|-------------|---------|
| GET | `/users` | List all users | Admin |
| POST | `/users` | Create new user | Admin |
| GET | `/users/{id}` | Get user details | Admin |
| PUT | `/users/{id}` | Update user | Admin |
| DELETE | `/users/{id}` | Delete user | Admin |

### Role & Permission Management
| Method | Endpoint | Description | Access |
|--------|----------|-------------|---------|
| GET | `/roles` | List all roles | Admin |
| POST | `/roles` | Create new role | Admin |
| GET | `/roles/{id}` | Get role details | Admin |
| PUT | `/roles/{id}` | Update role | Admin |
| POST | `/roles/{id}/permissions` | Assign permissions to role | Admin |
| GET | `/permissions` | List all permissions | Admin |

### Partner (Mitra) Management
| Method | Endpoint | Description | Access |
|--------|----------|-------------|---------|
| POST | `/mitra/register` | Register new partner | Admin |
| GET | `/mitra` | List all partners | Admin |
| GET | `/mitra/{id}` | Get partner details | Admin |
| POST | `/mitra/{id}/approve` | Approve partner | Admin |
| POST | `/mitra/{id}/reject` | Reject partner | Admin |
| POST | `/mitra/{id}/deactivate` | Deactivate partner | Admin |
| POST | `/mitra/{id}/reactivate` | Reactivate partner | Admin |
| PUT | `/mitra/{id}/fee` | Update partner fee | Admin |

### Vehicle Management
| Method | Endpoint | Description | Access |
|--------|----------|-------------|---------|
| GET | `/vehicles` | List vehicles | Admin, Partner |
| POST | `/vehicles` | Create vehicle | Admin |
| POST | `/vehicles/bulk/generate` | Generate bulk vehicle form | Admin |
| POST | `/vehicles/bulk` | Bulk create vehicles from form | Admin |
| GET | `/vehicles/{id}` | Get vehicle details | Admin, Partner |
| PUT | `/vehicles/{id}` | Update vehicle | Admin |
| DELETE | `/vehicles/{id}` | Delete vehicle | Admin |

### Route Management
| Method | Endpoint | Description | Access |
|--------|----------|-------------|---------|
| GET | `/routes` | List all routes | Admin |
| POST | `/routes` | Create new route | Admin |
| GET | `/routes/{id}` | Get route details | Admin |
| PUT | `/routes/{id}` | Update route | Admin |
| DELETE | `/routes/{id}` | Delete route | Admin |

### Schedule Management
| Method | Endpoint | Description | Access |
|--------|----------|-------------|---------|
| GET | `/schedules` | List all schedules | Admin |
| POST | `/schedules` | Create new schedule | Admin |
| GET | `/schedules/{id}` | Get schedule details | Admin |
| PUT | `/schedules/{id}` | Update schedule | Admin |
| DELETE | `/schedules/{id}` | Delete schedule | Admin |

### Seat Management
| Method | Endpoint | Description | Access |
|--------|----------|-------------|---------|
| GET | `/seats` | List seats (filter by vehicle_id) | Admin |
| POST | `/seats` | Create seat manually | Admin |
| POST | `/seats/generate` | Generate seats automatically | Admin |
| GET | `/seats/{id}` | Get seat details | Admin |
| PUT | `/seats/{id}` | Update seat | Admin |
| DELETE | `/seats/{id}` | Delete seat | Admin |

### Transaction Management
| Method | Endpoint | Description | Access |
|--------|----------|-------------|---------|
| POST | `/transactions/search` | Search available schedules | Partner |
| POST | `/transactions/seat-map` | Get seat map for schedule | Partner |
| POST | `/transactions/book` | Book tickets | Partner |
| POST | `/transactions/pay` | Process payment | Partner |
| GET | `/transactions/{trx_code}` | Get transaction details | Admin, Partner |
| POST | `/transactions/{trx_code}/issue` | Issue tickets | Partner |
| POST | `/transactions/{trx_code}/cancel` | Cancel transaction | Admin, Partner |

### Ticket Management
| Method | Endpoint | Description | Access |
|--------|----------|-------------|---------|
| GET | `/tickets/{id}` | Get ticket details | Admin, Partner |
| POST | `/tickets/{id}/reschedule` | Reschedule ticket | Admin, Partner |

### Top-up Management
| Method | Endpoint | Description | Access |
|--------|----------|-------------|---------|
| GET | `/topups` | List topups | Admin, Partner |
| POST | `/topups` | Create topup request | Partner |
| GET | `/topups/{id}` | Get topup details | Admin, Partner |
| POST | `/topups/{id}/approve` | Approve topup | Admin |
| POST | `/topups/{id}/reject` | Reject topup | Admin |

### Balance & Fee Management
| Method | Endpoint | Description | Access |
|--------|----------|-------------|---------|
| GET | `/balance` | Get balance info | Admin, Partner |
| GET | `/balance/histories` | Get balance histories | Admin, Partner |
| GET | `/fee/ledgers` | Get fee ledgers | Admin, Partner |
| GET | `/fee/config` | Get fee configuration | Admin, Partner |

### Reports
| Method | Endpoint | Description | Access |
|--------|----------|-------------|---------|
| GET | `/reports/transactions` | Transaction reports | Admin, Partner |
| GET | `/reports/topups` | Topup reports | Admin, Partner |
| GET | `/reports/fees` | Fee reports | Admin, Partner |
| GET | `/reports/balances` | Balance reports | Admin, Partner |
| GET | `/reports/export/{type}` | Export data (PDF) | Admin |
| POST | `/reports/export/combined` | Export combined data | Admin |

### Callbacks (No Authentication)
| Method | Endpoint | Description | Access |
|--------|----------|-------------|---------|
| POST | `/callbacks/provider/payment` | Payment callback | Public |
| POST | `/callbacks/provider/ticket` | Ticket callback | Public |

## Database Schema

### Core Tables
- **users**: System users (admin, partners)
- **roles**: User roles
- **permissions**: System permissions
- **mitra**: Partner information
- **routes**: Bus routes (origin-destination)
- **vehicles**: Bus vehicles
- **seats**: Vehicle seat layout
- **schedules**: Trip schedules
- **transactions**: Booking transactions
- **tickets**: Individual tickets
- **ticket_reschedules**: Ticket reschedule history

### Financial Tables
- **topups**: Balance top-up requests
- **balances**: User balance information
- **fee_ledgers**: Fee transaction records

## Key Features

### Role-Based Access Control
- **Admin**: Full system access
- **Partner (Mitra)**: Limited access to own resources

### Booking Flow
1. Search schedules → Seat map → Book → Pay → Issue tickets

### Bulk Vehicle Creation Flow
1. **Generate Form**: POST `/vehicles/bulk/generate` with initial parameters
   - Input: partner_id, count, base_name, seat_capacity, seat_layout
   - Output: Form data with generated vehicle names and plate numbers
2. **Edit Form**: Frontend allows editing of vehicle names and plate numbers
3. **Submit Form**: POST `/vehicles/bulk` with final vehicle data
   - Input: partner_id, vehicles[] array with name, plate_number, etc.
   - Output: Created vehicles in database

### Seat Management
- Automatic seat generation based on vehicle layout
- Support for 2-2, 2-3, 1-2 seat configurations

### Ticket Operations
- Individual ticket reschedule
- Transaction-level cancellation
- Complete audit trail

### Financial Management
- Partner balance system
- Fee calculation and tracking
- Top-up approval workflow

## Installation

1. Clone repository
2. Install dependencies: `composer install`
3. Setup environment: `cp .env.example .env`
4. Generate key: `php artisan key:generate`
5. Run migrations: `php artisan migrate`
6. Seed database: `php artisan db:seed`

## Testing Flow

### Phase 1: Admin Setup
1. Login as admin
2. Register and approve partners
3. Create routes, vehicles, seats, schedules

### Phase 2: Partner Operations
1. Login as partner
2. Search schedules and book tickets
3. Process payments and issue tickets

### Phase 3: Ticket Management
1. View transaction details
2. Reschedule individual tickets
3. Cancel transactions when needed

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).