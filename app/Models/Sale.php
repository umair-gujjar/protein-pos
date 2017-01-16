<?php

namespace App\Models;

/**
 * Class Sale
 *
 * @package App\Models
 */
class Sale extends BaseModel
{
    protected $casts = [
        'is_delivery' => 'boolean'
    ];
    protected $dates = [
        'opened_at', 'closed_at', 'cancelled_at', 'paid_at'
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function items()
    {
        return $this->hasMany(SaleItem::class);
    }

    public function packages()
    {
        return $this->hasMany(SalePackage::class);
    }

    public function payments()
    {
        return $this->hasMany(SalePayment::class);
    }

    public function getType()
    {
        return $this->is_delivery ? 'Delivery' : 'Walk In';
    }

    public function isFinished()
    {
        return $this->closed_at !== null;
    }

    public function isPaid()
    {
        return $this->paid_at !== null;
    }

    public function calculateSubTotal()
    {
        $subTotal = 0;

        /** @var SaleItem $item */
        foreach ($this->items as $item) {
            $subTotal += $item->calculateSubTotal();
        }

        /** @var SalePackage $package */
        foreach ($this->packages as $package) {
            $subTotal += $package->calculateSubTotal();
        }

        return $subTotal;
    }

    public function calculateAfterCustomerDiscount()
    {
        $subTotal = $this->calculateSubTotal();

        if ($this->customer->group) {
            $subTotal = $subTotal * (100 - $this->customer->group->discount) / 100;
        }

        return $subTotal;
    }

    public function calculateAfterSalesDiscount()
    {
        $subTotal = $this->calculateAfterCustomerDiscount();
        $subTotal = $subTotal * (100 - $this->sales_discount) / 100;

        return $subTotal;
    }

    public function calculateTotal()
    {
        return $this->calculateAfterSalesDiscount();
    }
}