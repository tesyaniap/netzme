# Bulk Vehicle Creation API

## Overview
Alur baru untuk bulk vehicle creation yang memungkinkan editing form sebelum menyimpan ke database.

## Flow
1. **Generate Form** → 2. **Edit Form (Frontend)** → 3. **Submit to Database**

---

## 1. Generate Bulk Vehicle Form

**Endpoint**: `POST /api/v1/vehicles/bulk/generate`

**Access**: Admin only

**Description**: Generate form data untuk bulk vehicle creation berdasarkan jumlah yang diinginkan.

### Request Body
```json
{
    "partner_id": 1,
    "count": 3,
    "base_name": "Bus Sinar Jaya",
    "seat_capacity": 40,
    "seat_layout": "2-2"
}
```

### Response Success (200)
```json
{
    "success": true,
    "data": {
        "partner": {
            "id": 1,
            "name": "PT Sinar Jaya Transport",
            "code": "SJ"
        },
        "vehicles": [
            {
                "index": 1,
                "name": "Bus Sinar Jaya 1",
                "plate_number": "SJ 001",
                "seat_capacity": 40,
                "seat_layout": "2-2"
            },
            {
                "index": 2,
                "name": "Bus Sinar Jaya 2",
                "plate_number": "SJ 002",
                "seat_capacity": 40,
                "seat_layout": "2-2"
            },
            {
                "index": 3,
                "name": "Bus Sinar Jaya 3",
                "plate_number": "SJ 003",
                "seat_capacity": 40,
                "seat_layout": "2-2"
            }
        ],
        "form_config": {
            "base_name": "Bus Sinar Jaya",
            "seat_capacity": 40,
            "seat_layout": "2-2",
            "count": 3
        }
    }
}
```

### Validation Rules
- `partner_id`: required, exists in mitra table
- `count`: required, integer, min:1, max:50
- `base_name`: required, string
- `seat_capacity`: required, integer, min:1
- `seat_layout`: required, in:2-2,2-3,1-2

---

## 2. Edit Form (Frontend)

Pada tahap ini, frontend menampilkan form yang bisa diedit:
- Nama bus bisa diubah
- Plat nomor bisa diubah
- Jumlah vehicle bisa ditambah/dikurangi
- Jika jumlah berubah, bisa call ulang endpoint generate untuk update form

---

## 3. Submit Bulk Vehicle Creation

**Endpoint**: `POST /api/v1/vehicles/bulk`

**Access**: Admin only

**Description**: Menyimpan data vehicles ke database berdasarkan form yang sudah diedit.

### Request Body
```json
{
    "partner_id": 1,
    "vehicles": [
        {
            "name": "Bus Sinar Jaya Premium 1",
            "plate_number": "B 1234 ABC",
            "seat_capacity": 40,
            "seat_layout": "2-2"
        },
        {
            "name": "Bus Sinar Jaya Premium 2", 
            "plate_number": "B 5678 DEF",
            "seat_capacity": 40,
            "seat_layout": "2-2"
        },
        {
            "name": "Bus Sinar Jaya Executive",
            "plate_number": "B 9012 GHI",
            "seat_capacity": 35,
            "seat_layout": "2-3"
        }
    ]
}
```

### Response Success (201)
```json
{
    "success": true,
    "message": "Successfully created 3 vehicles",
    "data": [
        {
            "id": 1,
            "name": "Bus Sinar Jaya Premium 1",
            "plate_number": "B 1234 ABC",
            "seat_capacity": 40,
            "seat_layout": "2-2",
            "status": "active",
            "partner": {
                "id": 1,
                "name": "PT Sinar Jaya Transport",
                "code": "SJ"
            }
        },
        {
            "id": 2,
            "name": "Bus Sinar Jaya Premium 2",
            "plate_number": "B 5678 DEF", 
            "seat_capacity": 40,
            "seat_layout": "2-2",
            "status": "active",
            "partner": {
                "id": 1,
                "name": "PT Sinar Jaya Transport",
                "code": "SJ"
            }
        },
        {
            "id": 3,
            "name": "Bus Sinar Jaya Executive",
            "plate_number": "B 9012 GHI",
            "seat_capacity": 35,
            "seat_layout": "2-3", 
            "status": "active",
            "partner": {
                "id": 1,
                "name": "PT Sinar Jaya Transport",
                "code": "SJ"
            }
        }
    ]
}
```

### Validation Rules
- `partner_id`: required, exists in mitra table
- `vehicles`: required, array, min:1, max:50
- `vehicles.*.name`: required, string
- `vehicles.*.plate_number`: required, string, unique in vehicles table
- `vehicles.*.seat_capacity`: required, integer, min:1
- `vehicles.*.seat_layout`: required, in:2-2,2-3,1-2

### Error Response (422)
```json
{
    "success": false,
    "message": "Validation failed",
    "errors": {
        "vehicles.0.plate_number": [
            "The vehicles.0.plate_number has already been taken."
        ],
        "vehicles.1.name": [
            "The vehicles.1.name field is required."
        ]
    }
}
```

---

## Frontend Implementation Notes

1. **Dynamic Form**: Form bisa menambah/mengurangi jumlah vehicle
2. **Real-time Validation**: Validasi plate number uniqueness
3. **Auto-generate**: Tombol untuk re-generate form jika parameter berubah
4. **Preview Mode**: Tampilkan preview sebelum submit final
5. **Batch Operations**: Support untuk edit multiple fields sekaligus

## Benefits

1. **Flexibility**: User bisa edit nama dan plat nomor sebelum save
2. **Safety**: Data tidak langsung masuk database
3. **User Experience**: Form yang lebih interaktif
4. **Validation**: Validasi di level form sebelum submit
5. **Scalability**: Mudah untuk extend dengan field tambahan