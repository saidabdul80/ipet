# Unit of Measurement (UOM) Conversion System

## 📋 Overview

This system allows you to **buy products in bulk units** (e.g., cartons) and **sell them in smaller units** (e.g., pieces), with automatic stock tracking in the base unit.

## 🎯 Problem Solved

**Before:**
- Purchase Order: "1 Carton" → Stock Ledger shows "1"
- Sale: "5 Pieces" → Stock Ledger shows "-5"
- **Result:** Incompatible units, incorrect stock balance

**After:**
- Purchase Order: "1 Carton (24 pcs)" → Stock Ledger shows "+24 pcs"
- Sale: "5 Pieces" → Stock Ledger shows "-5 pcs"
- **Result:** Accurate stock balance of 19 pieces

---

## 🏗️ Architecture

### 1. **Base Unit System**
Every product has a **base unit** (smallest sellable unit). All stock is tracked internally in this base unit.

**Example:**
- Product: "Coca-Cola"
- Base Unit: Piece (pcs)
- Alternative Units: Pack (6 pcs), Carton (24 pcs)

### 2. **Unit Hierarchy**

```
Piece (Base Unit)
├── Pack (6 pieces)
├── Carton (24 pieces)
└── Dozen (12 pieces)

Kilogram (Base Unit)
├── Gram (0.001 kg)
└── Ton (1000 kg)

Liter (Base Unit)
└── Milliliter (0.001 L)
```

### 3. **Conversion Flow**

```
Purchase Order (Carton) → Convert to Base Unit (Pieces) → Stock Ledger (Pieces)
                                                                ↓
Sale (Pieces) ← Convert from Base Unit ← Stock Ledger (Pieces)
```

---

## 📊 Database Schema

### **units** table
```sql
- id
- name (e.g., "Carton")
- short_name (e.g., "ctn")
- base_unit_id (references units.id) - NULL for base units
- conversion_factor (e.g., 24 for carton → pieces)
- is_active
```

### **product_units** table (Pivot)
```sql
- product_id
- unit_id
- conversion_factor (product-specific conversion)
- selling_price (price in this unit)
- cost_price (cost in this unit)
- is_purchase_unit (can buy in this unit)
- is_sale_unit (can sell in this unit)
- is_default
```

### **stock_ledger** table (Enhanced)
```sql
- unit_id (unit used in transaction)
- quantity (original quantity in original unit)
- base_quantity_change (converted to base unit)
- balance_quantity (running balance in base unit)
```

---

## 💻 Usage Examples

### Example 1: Beverage Product

**Setup:**
```php
Product: Coca-Cola 500ml
Base Unit: Piece (pcs)

ProductUnits:
- Carton: 24 pieces, ₦12,000 (₦500/piece)
- Pack: 6 pieces, ₦3,100 (₦516/piece)
- Piece: 1 piece, ₦550
```

**Purchase Order:**
```php
// Buy 10 cartons
PurchaseOrderItem:
- product_id: 1
- unit_id: 2 (Carton)
- quantity: 10
- unit_cost: ₦12,000

// System converts:
base_quantity = 10 × 24 = 240 pieces
```

**Stock Ledger Entry:**
```php
StockLedger:
- unit_id: 2 (Carton)
- quantity: 10 (cartons)
- base_quantity_change: +240 (pieces)
- balance_quantity: 240 (pieces)
```

**Sale:**
```php
// Sell 5 pieces
SaleItem:
- product_id: 1
- unit_id: 1 (Piece)
- quantity: 5
- unit_price: ₦550

// System converts:
base_quantity = 5 × 1 = 5 pieces
```

**Stock Ledger Entry:**
```php
StockLedger:
- unit_id: 1 (Piece)
- quantity: -5 (pieces)
- base_quantity_change: -5 (pieces)
- balance_quantity: 235 (pieces)
```

---

## 🔧 API Usage

### Get Available Units for Product
```php
$unitService = app(UnitConversionService::class);
$units = $unitService->getProductUnits($product, 'sale');

// Returns:
[
    ['id' => 1, 'name' => 'Piece', 'conversion_factor' => 1],
    ['id' => 3, 'name' => 'Pack', 'conversion_factor' => 6],
    ['id' => 2, 'name' => 'Carton', 'conversion_factor' => 24],
]
```

### Convert Quantity
```php
// Convert 2 cartons to pieces
$baseQty = $unitService->toBaseUnit($product, 2, $cartonUnitId);
// Result: 48 pieces

// Convert 48 pieces to cartons
$cartonQty = $unitService->fromBaseUnit($product, 48, $cartonUnitId);
// Result: 2 cartons
```

### Record Stock Transaction
```php
$stockService->recordTransaction(
    store: $store,
    product: $product,
    transactionType: 'receipt',
    quantity: 10,           // 10 cartons
    unitCost: 12000,
    unitId: $cartonUnitId   // Carton unit
);
// Automatically converts to 240 pieces in stock ledger
```

---

## 📝 Implementation Checklist

### Backend (Completed ✅)
- [x] Database migrations
- [x] UnitConversionService
- [x] ProductUnit model
- [x] StockService enhancement
- [x] ProductUnitController API
- [x] API routes
- [x] Unit conversion seeder

### Backend (Pending)
- [ ] Update PurchaseOrderController to accept unit_id
- [ ] Update SaleController to accept unit_id
- [ ] Update InventoryController for unit display

### Frontend (Pending)
- [ ] Unit selector in Purchase Order form
- [ ] Unit selector in POS interface
- [ ] Display conversions in real-time
- [ ] Product unit management UI
- [ ] Stock display with unit options

### Testing (Pending)
- [ ] Unit conversion tests
- [ ] Purchase with different units
- [ ] Sale with different units
- [ ] Stock balance accuracy

---

## 🎓 Best Practices

1. **Always define base unit** for each product
2. **Use product_units** for product-specific conversions
3. **Stock ledger** always stores balance in base unit
4. **Display** can show any unit, but calculations use base unit
5. **Barcode support** - different barcodes for different units

---

## 🚀 Quick Start Guide

### Step 1: Run Migrations
```bash
php artisan migrate
```

### Step 2: Seed Unit Conversions
```bash
php artisan db:seed --class=UnitConversionSeeder
```

### Step 3: Configure Product Units (via API or Database)

**Option A: Via API**
```bash
POST /api/products/1/units
{
    "unit_id": 2,
    "conversion_factor": 24,
    "selling_price": 12000,
    "cost_price": 11000,
    "is_purchase_unit": true,
    "is_sale_unit": true
}
```

**Option B: Via Database**
```sql
INSERT INTO product_units (product_id, unit_id, conversion_factor, selling_price, is_purchase_unit, is_sale_unit)
VALUES (1, 2, 24, 12000, 1, 1);
```

### Step 4: Update Controllers (Next Phase)

**PurchaseOrderController:**
```php
// Add unit_id to validation
'items.*.unit_id' => 'nullable|exists:units,id',

// Pass unit_id to stock service
$this->stockService->recordTransaction(
    $store,
    $product,
    'receipt',
    $item['quantity_received'],
    $poItem->unit_cost,
    $poItem->product_variant_id,
    'goods_received_note',
    $grn->id,
    "GRN: {$grn->grn_number}",
    null,
    $item['unit_id'] ?? null  // ← Add this
);
```

**SaleController:**
```php
// Add unit_id to validation
'items.*.unit_id' => 'nullable|exists:units,id',

// Pass unit_id to stock service
$this->stockService->recordTransaction(
    $store,
    $product,
    'issue',
    $item['quantity'],
    $product->cost_price,
    $item['product_variant_id'] ?? null,
    'sale',
    $sale->id,
    "Sale: {$sale->invoice_number}",
    null,
    $item['unit_id'] ?? null  // ← Add this
);
```

### Step 5: Test the System

**Test Scenario 1: Purchase in Cartons, Sell in Pieces**
1. Create PO: 10 cartons @ ₦12,000/carton
2. Receive goods → Stock ledger shows +240 pieces
3. Sell 5 pieces @ ₦550/piece
4. Check stock → Should show 235 pieces (or 9.79 cartons)

**Test Scenario 2: Mixed Unit Sales**
1. Stock: 240 pieces
2. Sell 1 carton (24 pcs) → Stock: 216 pieces
3. Sell 2 packs (12 pcs) → Stock: 204 pieces
4. Sell 4 pieces → Stock: 200 pieces

---

## 🔍 Troubleshooting

### Issue: "Cannot convert between incompatible units"
**Cause:** Trying to convert between units that don't share a base unit
**Solution:** Ensure units have proper base_unit_id relationship

### Issue: Stock balance incorrect after conversion
**Cause:** Conversion factor not set correctly
**Solution:** Verify conversion_factor in product_units table

### Issue: Unit not showing in dropdown
**Cause:** is_purchase_unit or is_sale_unit not set
**Solution:** Update product_units record with correct flags

---

## 📞 Support

For questions or issues with the unit conversion system:
1. Check the documentation above
2. Review the code in `app/Services/UnitConversionService.php`
3. Test conversions using the API endpoint: `POST /api/products/{id}/units/convert`

