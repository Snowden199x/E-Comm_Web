<?php

namespace App\Models\Complaints;

use App\Models\Ecommerce\Order;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Attributes\Fillable;

#[Fillable(['order_id', 'complainant_id', 'respondent_id', 'type', 'description', 'status'])]
class Complaint extends Model
{
    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function complainant()
    {
        return $this->belongsTo(User::class, 'complainant_id');
    }

    public function respondent()
    {
        return $this->belongsTo(User::class, 'respondent_id');
    }

    public function evidences()
    {
        return $this->hasMany(ComplaintEvidence::class);
    }

    public function activities()
    {
        return $this->hasMany(ComplaintActivity::class)->latest();
    }

    public function getTypeColorsAttribute(): array
    {
        $map = [
            'Item Not Received' => ['border' => '#9A4A00', 'bg' => '#FCE8D2'],
            'Wrong Item Received' => ['border' => '#7A4E00', 'bg' => '#F7E9C6'],
            'Damaged Item' => ['border' => '#A33A3A', 'bg' => '#F8DADA'],
            'Missing Item' => ['border' => '#805A00', 'bg' => '#F5E7B8'],
            'Item Not as Described' => ['border' => '#7A3F72', 'bg' => '#EEDDF0'],
            'Payment Issue' => ['border' => '#315A8A', 'bg' => '#DCE8F5'],
            'Refund Issue' => ['border' => '#4C5C8A', 'bg' => '#E1E6F5'],
            'Seller Issue' => ['border' => '#8A3F3F', 'bg' => '#F2DADA'],
            'Delivery Delay' => ['border' => '#A35C00', 'bg' => '#F9E2C7'],
            'Delivery Issue' => ['border' => '#365F70', 'bg' => '#DCECEF'],
            'Courier Issue' => ['border' => '#596B3D', 'bg' => '#E4ECD7'],
        ];

        return $map[$this->type] ?? ['border' => '#666666', 'bg' => '#E9E9E9'];
    }

    public function getStatusColorsAttribute(): array
    {
        $map = [
            'open' => ['border' => '#B45309', 'bg' => '#FEF3C7', 'label' => 'Open'],
            'in_review' => ['border' => '#2563EB', 'bg' => '#DBEAFE', 'label' => 'In Progress'],
            'resolved' => ['border' => '#15803D', 'bg' => '#DCFCE7', 'label' => 'Resolved'],
        ];

        return $map[$this->status] ?? ['border' => '#666666', 'bg' => '#E9E9E9', 'label' => ucfirst($this->status)];
    }
}