# Api's

### FACTURACION

- **Crear una factura electronica**

  - Method : POST
  - URL: /siigo/v2/invoices

Informacion completa para crear una factura.

| Attribute                   | Type    | Required | Description                                                                                              | Example        | Default                      |
| :-------------------------- | :------ | :------- | :------------------------------------------------------------------------------------------------------- | :------------- | :--------------------------- |
| `customer`                  | Object  | SI       | Datos del cliente.                                                                                       |                |                              |
| `customer`.`identification` | String  | SI       | Número de identificación del cliente.                                                                                |                |                              |
| `customer`.`first_name`     | String  | SI       | Nombre del cliente.                                                                                      |                |                              |
| `customer`.`last_name`      | String  | SI       | Apellido del cliente.                                                                                    |                |                              |
| `loan`                      | Object  | SI       | Datos del prestamo.                                                                                      |                |                              |
| `loan.id`                   | Integer | SI       | Número de préstamo.                                                                                      | 123            |                              |
| `loan.quota`                | Integer | SI       | Número de Cuota.                                                                                         | 1              |                              |
| `Items`                     | Object  | SI       | Es un array de objetos con todos los items que se van a facturar                                         |                |                              |
| `Items[].code`              | String  | SI       | Especificacion del producto que se va a facturar. Ver tablas de productos.                               | Fianza         |                              |
| `Items[].quantity`          | Decimal | SI       | Valor unitario     Cantidad de productos                                                                 | 1              | 1                            |
| `Items[].description`       | String  | SI       | Descripción del producto                                                                                 | Valor de cuota |                              |
| `Items[].price`             | Integer | SI       | Valor unitario                                                                                           | 5000           |                              |
| `payments`                  | Object  | NO       | (Opcional)Es un array de objetos con todos los medios de pago.                                           |                |                              |
| `payments[].id`             | Integer | NO       | (Opcional)Id del medio de pago, ver tabla de medios de pagos.                                            | 5636           | 5636                         |
| `payments[].due_date`       | Date    | NO       | (Opcional)Fecha de vencimiento formato yyyy-MM-dd, por defecto toma la fecha actual si no se la indica.  | 2022-03-19     | Fecha actual                 |
| `options`                   |         |          | (Opcional) Array con informacion adicional.                                                              |                |                              |
| `options`.`date`            | Date    | NO       | (Opcional) Fecha de creación en formato yyyy-MM-dd, por defecto toma la fecha actual si no se la indica. | 2022-02-16     | Fecha actual                 |
| `options`.`number`          | Integer | NO       | (Opcional) Número de factura, por defecto toma a partir del ultimo registrado en siigo_invoices.         | 1234           |                              |
| `options`.`branch_office`   | Integer | NO       | (Opcional) Sucursal, valor por default 0.                                                                | 0              | 0                            |
| `options`.`seller`          | String  | NO       | (Opcional) Id del vendedor. Por defecto toma el seteado en Siigo Api.                                    |                | contabilidad@creditek.com.co |
| `options`.`observations`    | String  | NO       | (Opcional) Leyenda en la factura. Por omisión toma lo que este configurado en .env                       |                |                              |
| `options`.`retentions`      | String  | NO       | (Opcional) Array con los id de los impuestos tipo ReteICA, ReteIVA o Autoretención                       |                |                              |
| `options`.`advance_payment` | Integer | NO       | (Opcional) Valor de Anticipo o Copago.                                                                   |                |                              |
| `options`.`cost_center	`    | Integer | NO       | (Opcional) Identificador del Centro de costos.                                                           |                |                              |

Ejemplo resumida para crear una factura.

```json
{
    "customer": {
        "identification": "53037390",
        "first_name": "Luz Viviana",
        "last_name": "Fuentes"
    },
    "loan": {
        "id": 123,
        "quota": 1
    },
    "items" : [
        {
            "code" : "CR200001",
            "description" : "Capital cuota N° 1",
            "quantity" : 1,
            "price" : 31520.0
        },
        {
            "code" : "CR100001",
            "description" : "Interes cuota N° 1",
            "quantity" : 1,
            "price" : 3721.0
        },
        {
            "code" : "FZ100001",
            "description" : "Fianza cuota N° 1",
            "quantity" : 1,
            "price" : 25000.0
        },
        {
            "code" : "FZ100002",
            "description" : "IVA fianza cuota N° 1",
            "quantity" : 1,
            "price" : 4750.0
        }
    ],
    "options" : {
        "date" : "2022-02-15",
        "number": 49857
    }
}
```

_Ejemplo de respuesta:_

**_200 Ok_**

```json
{
    "success": true,
    "code": 0,
    "locale": "en",
    "message": "OK",
    "data": {
        "id": "ec0af530-e878-4ea8-bd2d-fbc852cac9c5",
        "document": {
            "id": 18503
        },
        "number": 49857,
        "name": "FV-1-49857",
        "date": "2022-02-15",
        "customer": {
            "id": "e86fa2de-c9db-43ff-a62f-cb359f2cc517",
            "identification": "53037390",
            "branch_office": 0
        },
        "seller": 808,
        "total": 64991,
        "balance": 0,
        "observations": "Si ya realizó su pago, por favor hacer caso omiso.",
        "items": [
            {
                "id": "a633e74d-a593-4f2b-a996-b8468511891c",
                "code": "CR200001",
                "quantity": 1,
                "price": 31520,
                "description": "Capital cuota N° 1",
                "total": 31520
            },
            {
                "id": "6331d36d-b09b-4976-bc4a-22a8cddad5c4",
                "code": "CR100001",
                "quantity": 1,
                "price": 3721,
                "description": "Interes cuota N° 1",
                "total": 3721
            },
            {
                "id": "f687b6a6-9546-4f1d-88d2-f079cf175083",
                "code": "FZ100001",
                "quantity": 1,
                "price": 25000,
                "description": "Fianza cuota N° 1",
                "total": 25000
            },
            {
                "id": "18014b3d-ed35-434a-8e0a-a44530f5ffc2",
                "code": "FZ100002",
                "quantity": 1,
                "price": 4750,
                "description": "IVA fianza cuota N° 1",
                "total": 4750
            }
        ],
        "payments": [
            {
                "id": 4528,
                "name": "Efectivo",
                "value": 64991
            }
        ],
        "stamp": {
            "status": "Draft"
        },
        "mail": {
            "status": "not_sent",
            "observations": "The invoice has not been sent by mail"
        },
        "metadata": {
            "created": "2022-02-21T00:15:01.427"
        }
    }
}
```

- **Obtener listado de las facturas creadas**

  - Method : GET
  - URL: /api/invoices

  _Ejemplo de respuesta:_

**_400 Error_**

```json
{
    "success": false,
    "code": 400,
    "locale": "en",
    "message": "Error en Siigo Api",
    "data": {
        "already_exists": "The number already exists: 49857"
    }
}
```