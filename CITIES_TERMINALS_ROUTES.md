# Cities, Terminals & Routes Structure

## Database Structure

### 1. Cities Table
```
cities
├── id
├── name
├── province
├── created_at
└── updated_at
```

### 2. Terminals Table
```
terminals
├── id
├── city_id (FK → cities)
├── name
├── address
├── created_at
└── updated_at
```

### 3. Routes Table
```
routes
├── id
├── origin_city_id (FK → cities)
├── destination_city_id (FK → cities)
├── departure_terminal_id (FK → terminals)
├── arrival_terminal_id (FK → terminals)
├── distance
├── created_at
└── updated_at
```

## Relationships

```
City
  ├── hasMany → Terminal
  ├── hasMany → Route (as origin_city)
  └── hasMany → Route (as destination_city)

Terminal
  ├── belongsTo → City
  ├── hasMany → Route (as departure_terminal)
  └── hasMany → Route (as arrival_terminal)

Route
  ├── belongsTo → City (origin_city)
  ├── belongsTo → City (destination_city)
  ├── belongsTo → Terminal (departure_terminal)
  ├── belongsTo → Terminal (arrival_terminal)
  └── hasMany → Schedule
```

## API Endpoints

### Cities (Admin Only)
- `GET /api/v1/cities` - List all cities
- `POST /api/v1/cities` - Create city
- `GET /api/v1/cities/{id}` - Show city
- `PUT /api/v1/cities/{id}` - Update city
- `DELETE /api/v1/cities/{id}` - Delete city

### Terminals (Admin Only)
- `GET /api/v1/terminals` - List all terminals (filter: ?city_id=1)
- `POST /api/v1/terminals` - Create terminal
- `GET /api/v1/terminals/{id}` - Show terminal
- `PUT /api/v1/terminals/{id}` - Update terminal
- `DELETE /api/v1/terminals/{id}` - Delete terminal

### Routes (Admin Only)
- `GET /api/v1/routes` - List all routes
- `POST /api/v1/routes` - Create route
- `GET /api/v1/routes/{id}` - Show route
- `PUT /api/v1/routes/{id}` - Update route
- `DELETE /api/v1/routes/{id}` - Delete route

## Permissions

### Admin
- cities.view, cities.create, cities.update, cities.delete
- terminals.view, terminals.create, terminals.update, terminals.delete
- routes.view, routes.create, routes.update, routes.delete

### Mitra
- cities.view
- terminals.view
- routes.view

## Sample Data

### Cities
1. Kota Cimahi (Jawa Barat)
2. Banda Aceh (Aceh)
3. Jakarta (DKI Jakarta)
4. Bandung (Jawa Barat)
5. Surabaya (Jawa Timur)

### Terminals
1. Terminal Cimahi (Kota Cimahi)
2. Terminal Batoh (Banda Aceh)
3. Terminal Kampung Rambutan (Jakarta)
4. Terminal Leuwi Panjang (Bandung)
5. Terminal Purabaya (Surabaya)

## Example Request

### Create City
```json
POST /api/v1/cities
{
  "name": "Yogyakarta",
  "province": "DI Yogyakarta"
}
```

### Create Terminal
```json
POST /api/v1/terminals
{
  "city_id": 1,
  "name": "Terminal Giwangan",
  "address": "Jl. Imogiri Timur"
}
```

### Create Route
```json
POST /api/v1/routes
{
  "origin_city_id": 1,
  "destination_city_id": 3,
  "departure_terminal_id": 1,
  "arrival_terminal_id": 3,
  "distance": 450
}
```
