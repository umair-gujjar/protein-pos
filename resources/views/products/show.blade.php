@extends('layouts.app')

@section('title')
    - {{ $product->name }}
@endsection

@section('content')
    @parent
    <div id="app">
        <div class="row">
            <div class="col-md-6">
                <div class="panel panel-default">
                    <div class="panel-heading">
                        General - {{ $product->name }} {{ $product->is_service ? '(Service Item)' : '' }}
                        @if($product->isBulkContainer())
                            &nbsp;
                            <span class="label label-info">
                                <i class="fa fa-info-circle"></i>
                                Container
                            </span>
                        @endif
                    </div>
                    <div class="panel-body form-horizontal">
                        <div class="row">
                            <label for="name" class="col-sm-2 control-label">Name</label>
                            <div class="col-sm-10">
                                <p class="form-control-static">{{ $product->name }}</p>
                            </div>
                        </div>
                        <div class="row">
                            <label for="price" class="col-sm-2 control-label">Price</label>
                            <div class="col-sm-10">
                                <p class="form-control-static">@money($product->price)</p>
                            </div>
                        </div>
                        <div class="row">
                            <label for="code" class="col-sm-2 control-label">Code</label>
                            <div class="col-sm-2">
                                <p class="form-control-static">{{ $product->code }}</p>
                            </div>
                            <label for="barcode" class="col-sm-2 control-label">Barcode</label>
                            <div class="col-sm-6">
                                <p class="form-control-static">{{ $product->barcode }}</p>
                            </div>
                        </div>
                        @if(!$product->is_service)
                            <div class="row">
                                <label for="brand" class="col-sm-2 control-label">Brand</label>
                                <div class="col-sm-10">
                                    @if($product->brand)
                                        <p class="form-control-static">{{ $product->brand->name }}</p>
                                    @else
                                        <p class="form-control-static"></p>
                                    @endif
                                </div>
                            </div>
                            <div class="row">
                                <label for="category" class="col-sm-2 control-label">Category</label>
                                <div class="col-sm-10">
                                    @if($product->category)
                                        <p class="form-control-static">{{ $product->category->parent->name.', '.$product->category->name }}</p>
                                    @else
                                        <p class="form-control-static"></p>
                                    @endif
                                </div>
                            </div>
                            @if($product->isBulkContainer())
                                <div class="row">
                                    <label for="brand" class="col-sm-2 control-label">Content</label>
                                    <div class="col-sm-10">
                                        <p class="form-control-static">
                                            <a href="{{ route('products.show', $product->item->id) }}" target="_blank">{{ $product->item->name }}</a>
                                        </p>
                                    </div>
                                </div>
                            @endif
                        @endif
                        @can('update', \App\Models\Product::class)
                            <div class="form-group">
                                <div class="col-sm-offset-2 col-sm-10">
                                    <div class="row">
                                        <div class="col-sm-4">
                                            <a href="{{ route('products.edit', $product->id) }}" class="btn btn-primary btn-block">
                                                <i class="fa fa-pencil fa-fw"></i>
                                                Edit
                                            </a>
                                        </div>
                                        <div class="col-sm-4">
                                            <a href="{{ Session::get('last_product_page') ?: route('products.index') }}" class="btn btn-default btn-block">
                                                <i class="fa fa-arrow-left fa-fw"></i>
                                                Back
                                            </a>
                                        </div>
                                        @can('delete', \App\Models\Product::class)
                                            <div class="col-sm-4">
                                                <form method="post" action="{{ route('products.destroy', $product->id) }}" class="inline" @if($inventories->count() > 0) onsubmit="return confirm('Product contains stock! Delete?');" @endif>
                                                    {{ method_field('DELETE') }}
                                                    {{ csrf_field() }}
                                                    <button type="submit" class="btn btn-danger btn-block">
                                                        <i class="fa fa-trash"></i>
                                                        Delete
                                                    </button>
                                                </form>
                                            </div>
                                        @endcan
                                    </div>
                                </div>
                            </div>
                        @endcan
                    </div>
                </div>
            </div>
            @if(!$product->is_service)
                <div class="col-md-6">
                    <div class="panel panel-default">
                        <div class="panel-heading">
                            Stock
                        </div>
                        <div class="panel-body form-horizontal">
                            <div class="row">
                                <label for="stock-in-hand" class="col-sm-3 control-label">Stock In Hand</label>
                                <div class="col-sm-9">
                                    <p class="form-control-static">{{ number_format($inventories->sum('stock')).' ('.number_format($inventories->sum('stock') - $expiredInventories->sum('stock')).' available, '.$expiredInventories->sum('stock').' expired)' }}</p>
                                </div>
                            </div>
                            <div class="row">
                                <label for="closest-expiry" class="col-sm-3 control-label">Closest Expiry</label>
                                <div class="col-sm-9">
                                    <p class="form-control-static">{{ $closestExpired ? $closestExpired->expired_at->toFormattedDateString().' ('.$closestExpired->stock.' items)' : '-' }}</p>
                                </div>
                            </div>
                            @can('update', \App\Models\Product::class)
                                <div class="form-group">
                                    <div class="col-sm-offset-3 col-sm-9">
                                        <div class="row">
                                            <div class="col-sm-6">
                                                @if($inventories->sum('stock') > 0)
                                                    <a href="#move-inventory-modal" class="btn btn-primary btn-block" data-toggle="modal">
                                                        <i class="fa fa-arrow-right fa-fw"></i>
                                                        Move To Other Branch
                                                    </a>
                                                @endif
                                            </div>
                                            <div class="col-sm-6">
                                                @if($inventories->sum('stock') > 0)
                                                    <a href="#remove-inventory-modal" class="btn btn-danger btn-block" data-toggle="modal">
                                                        <i class="fa fa-trash fa-fw"></i>
                                                        Remove
                                                    </a>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endcan
                            @can('seeAllBranch', \App\Models\Product::class)
                                @if($branches->count() > 0)
                                    <table class="table table-bordered">
                                        <thead>
                                        <tr>
                                            <th>Branch</th>
                                            <th>Stock</th>
                                            <th>Closest Expiry</th>
                                        </tr>
                                        </thead>
                                        <tbody>
                                        @foreach($branches as $branch)
                                            <tr>
                                                <td>{{ $branch->name }}</td>
                                                <td>
                                                    {{ number_format($branch->branchInventories->sum('stock')).' ('.number_format($branch->branchInventories->sum('stock') - $branch->expiredBranchInventories->sum('stock')).' available, '.$branch->expiredBranchInventories->sum('stock').' expired)' }}
                                                </td>
                                                <td>
                                                    {{ $branch->closestExpiredInventory ? $branch->closestExpiredInventory->expired_at->toFormattedDateString().' ('.$branch->closestExpiredInventory->stock.' items)' : '-' }}
                                                </td>
                                            </tr>
                                        @endforeach
                                        </tbody>
                                    </table>
                                @else
                                    <p>No movement yet</p>
                                @endif
                            @endcan
                        </div>
                    </div>
                </div>
            @endif
        </div>
        @can('seeAllBranch', \App\Models\Product::class)
            @if(!$product->is_service)
                <div class="row">
                    <div class="col-md-12">
                        <div class="panel panel-default">
                            <div class="panel-heading">
                                Inventory Details
                            </div>
                            <div class="panel-body">
                                <ul class="nav nav-tabs" role="tablist">
                                    <li role="presentation" class="active">
                                        <a href="#breakdown" aria-controls="breakdown" role="tab" data-toggle="tab">
                                            Breakdown - Current Branch
                                        </a>
                                    </li>
                                    <li role="presentation">
                                        <a href="#movement" aria-controls="movement" role="tab" data-toggle="tab">
                                            Movements
                                        </a>
                                    </li>
                                    @foreach($otherBranches as $branch)
                                        <li role="presentation">
                                            <a href="#breakdown-{{ $branch->id }}" aria-controls="breakdown" role="tab" data-toggle="tab">
                                                Breakdown - {{ $branch->name }}
                                            </a>
                                        </li>
                                    @endforeach
                                </ul>
                                <div class="tab-content">
                                    <br/>
                                    <div role="tabpanel" class="tab-pane active" id="breakdown">
                                        @include('products.components.branch-inventory-table', ['inventories' => $branches[Auth::user()->branch_id]->branchInventories])
                                    </div>
                                    <div role="tabpanel" class="tab-pane" id="movement">
                                        @if(count($movements) > 0)
                                            <table class="table table-bordered">
                                                <thead>
                                                <tr>
                                                    <th>Movement</th>
                                                    <th>Quantity</th>
                                                    <th>Date</th>
                                                    <th>Admin</th>
                                                    <th>Remark</th>
                                                    {{--<th></th>--}}
                                                </tr>
                                                </thead>
                                                <tbody>
                                                @foreach($movements as $movement)
                                                    <tr>
                                                        <td>{{ $movement['label'] }}</td>
                                                        <td>{{ $movement['quantity'] }}</td>
                                                        <td>{{ $movement['dateString'] }}</td>
                                                        <td>{{ $movement['actor'] }}</td>
                                                        <td>{{ $movement['remark'] }}</td>
                                                        {{--<td>--}}
                                                        {{--<a href="" class="btn btn-primary btn-sm" target="_blank">--}}
                                                        {{--<i class="fa fa-search-plus"></i>--}}
                                                        {{--See detail--}}
                                                        {{--</a>--}}
                                                        {{--</td>--}}
                                                    </tr>
                                                @endforeach
                                                </tbody>
                                            </table>
                                        @else
                                            <p>No movement yet</p>
                                        @endif
                                        @can('update', \App\Models\Product::class)
                                            <div class="row">
                                                <div class="col-sm-4">
                                                    <a href="#add-inventory-modal" class="btn btn-primary" data-toggle="modal">
                                                        <i class="fa fa-plus"></i>
                                                        Add Inventory
                                                    </a>
                                                </div>
                                            </div>
                                        @endcan
                                    </div>
                                    @foreach($otherBranches as $branch)
                                        <div role="tabpanel" class="tab-pane" id="breakdown-{{ $branch->id }}">
                                            @include('products.components.branch-inventory-table', ['inventories' => $branch->branchInventories])
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal fade" id="remove-inventory-modal" tabindex="-1" role="dialog" aria-labelledby="remove-inventory-modal-label">
                    <div class="modal-dialog" role="document">
                        <form class="form-horizontal" method="post" action="{{ route('products.inventory.remove', $product->id) }}">
                            {{ csrf_field() }}
                            <div class="modal-content">
                                <div class="modal-header">
                                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                                    <h4 class="modal-title" id="remove-inventory-modal-label">Remove Inventory</h4>
                                </div>
                                <div class="modal-body">
                                    <div class="row">
                                        <div class="col-sm-12">
                                            <div class="form-group">
                                                <label class="control-label col-sm-4">Product</label>
                                                <div class="col-sm-8">
                                                    <p class="form-control-static">{{ $product->name }}</p>
                                                </div>
                                            </div>
                                            <div class="form-group {{ $errors->has('inventory_id') ? 'has-error' : '' }}">
                                                <label class="control-label col-sm-4" for="branch_id">From Branch</label>
                                                <div class="col-sm-6">
                                                    <select class="form-control" name="branch_id" v-model="selectedBranchId" required>
                                                        <option v-bind:value="null">Select Branch</option>
                                                        <option v-for="branch in branches" v-bind:value="branch.id">@{{ branch.name }}</option>
                                                    </select>
                                                    @foreach($errors->get('branch_id') as $error)
                                                        <span class="label label-danger">{{ $error }}</span>
                                                    @endforeach
                                                </div>
                                            </div>
                                            <div class="form-group {{ $errors->has('inventory_id') ? 'has-error' : '' }}">
                                                <label class="control-label col-sm-4" for="inventory_id">Priority</label>
                                                <div class="col-sm-6" id="current-stock">
                                                    <select class="form-control" name="branch_inventory_id" required>
                                                        <option value>Select Priority</option>
                                                        <template v-for="inventory in selectedBranch.inventories">
                                                            <option v-for="item in inventory.items" v-bind:value="item.id">Priority @{{ item.priority }} (@{{ item.stock }})</option>
                                                        </template>
                                                    </select>
                                                    @foreach($errors->get('inventory_id') as $error)
                                                        <span class="label label-danger">{{ $error }}</span>
                                                    @endforeach
                                                </div>
                                            </div>
                                            <div class="form-group {{ $errors->has('quantity') ? 'has-error' : '' }}">
                                                <label class="control-label col-sm-4" for="quantity">Quantity</label>
                                                <div class="col-sm-2" id="current-stock">
                                                    <input type="text" name="quantity" id="quantity" class="form-control" value="{{ old('quantity') ?: 0 }}" required />
                                                    @foreach($errors->get('quantity') as $error)
                                                        <span class="label label-danger">{{ $error }}</span>
                                                    @endforeach
                                                </div>
                                            </div>
                                            <div class="form-group {{ $errors->has('remark') ? 'has-error' : '' }}">
                                                <label class="control-label col-sm-4">Remark</label>
                                                <div class="col-sm-8">
                                                    <textarea class="form-control" name="remark">{{ old('remark') }}</textarea>
                                                    @foreach($errors->get('remark') as $error)
                                                        <span class="label label-danger">{{ $error }}</span>
                                                    @endforeach
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-default" data-dismiss="modal">
                                        <i class="fa fa-times fa-fw"></i>
                                        Close
                                    </button>
                                    <button type="submit" class="btn btn-danger">
                                        <i class="fa fa-trash fa-fw"></i>
                                        Remove
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
                <div class="modal fade" id="move-inventory-modal" tabindex="-1" role="dialog" aria-labelledby="move-inventory-modal-label">
                    <div class="modal-dialog" role="document">
                        <form class="form-horizontal" method="post" action="{{ route('products.inventory.move', $product->id) }}">
                            {{ csrf_field() }}
                            <div class="modal-content">
                                <div class="modal-header">
                                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                                    <h4 class="modal-title" id="move-inventory-modal-label">Move Inventory</h4>
                                </div>
                                <div class="modal-body">
                                    <div class="row">
                                        <div class="col-sm-12">
                                            <div class="form-group">
                                                <label class="control-label col-sm-4">Product</label>
                                                <div class="col-sm-8">
                                                    <p class="form-control-static">{{ $product->name }}</p>
                                                </div>
                                            </div>
                                            <div class="form-group {{ $errors->has('quantity') ? 'has-error' : '' }}">
                                                <label class="control-label col-sm-4" for="quantity">Quantity</label>
                                                <div class="col-sm-2" id="current-stock">
                                                    <input type="text" name="quantity" id="quantity" class="form-control" value="{{ old('quantity') ?: 0 }}" required />
                                                    @foreach($errors->get('quantity') as $error)
                                                        <span class="label label-danger">{{ $error }}</span>
                                                    @endforeach
                                                </div>
                                            </div>
                                            <div class="form-group {{ $errors->has('branch_id') ? 'has-error' : '' }}">
                                                <label class="control-label col-sm-4" for="branch-id">To Branch</label>
                                                <div class="col-sm-8">
                                                    <select class="form-control" name="branch_id" id="branch-id" required>
                                                        <option value @if(old('branch_id') === null) selected @endif>Select Branch</option>
                                                        @foreach($otherBranches as $otherBranch)
                                                            <option value="{{ $otherBranch->id }}" @if(old('branch_id') == $otherBranch->id) selected @endif>{{ $otherBranch->name }}</option>
                                                        @endforeach
                                                    </select>
                                                    @foreach($errors->get('branch_id') as $error)
                                                        <span class="label label-danger">{{ $error }}</span>
                                                    @endforeach
                                                </div>
                                            </div>
                                            <div class="form-group {{ $errors->has('inventory_id') ? 'has-error' : '' }}">
                                                <label class="control-label col-sm-4" for="inventory_id">Priority</label>
                                                <div class="col-sm-6" id="current-stock">
                                                    <select class="form-control" name="inventory_id" required>
                                                        <option value>Select Priority</option>
                                                        @foreach($currentBranch->branchInventories as $inventory)
                                                            @if($inventory->stock > 0)
                                                                @foreach($inventory->branchItems as $branchItem)
                                                                    <option value="{{ $branchItem->id }}" @if($branchItem->id == old('inventory_id')) selected @endif>Priority {{ $branchItem->priority }} ({{ number_format($branchItem->stock) }} items)</option>
                                                                @endforeach
                                                            @endif
                                                        @endforeach
                                                    </select>
                                                    @foreach($errors->get('inventory_id') as $error)
                                                        <span class="label label-danger">{{ $error }}</span>
                                                    @endforeach
                                                </div>
                                            </div>
                                            <div class="form-group {{ $errors->has('remark') ? 'has-error' : '' }}">
                                                <label class="control-label col-sm-4">Remark</label>
                                                <div class="col-sm-8">
                                                    <textarea class="form-control" name="remark">{{ old('remark') }}</textarea>
                                                    @foreach($errors->get('remark') as $error)
                                                        <span class="label label-danger">{{ $error }}</span>
                                                    @endforeach
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-default" data-dismiss="modal">
                                        <i class="fa fa-times fa-fw"></i>
                                        Close
                                    </button>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fa fa-floppy-o fa-fw"></i>
                                        Save
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
                <div class="modal fade" id="add-inventory-modal" tabindex="-1" role="dialog" aria-labelledby="add-inventory-modal-label">
                    <div class="modal-dialog" role="document">
                        <form class="form-horizontal" method="post" action="{{ route('products.inventory.add', $product->id) }}">
                            {{ csrf_field() }}
                            <div class="modal-content">
                                <div class="modal-header">
                                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                                    <h4 class="modal-title" id="add-inventory-modal-label">Add Inventory</h4>
                                </div>
                                <div class="modal-body">
                                    <div class="row">
                                        <div class="col-sm-12">
                                            <div class="form-group">
                                                <label class="control-label col-sm-4">Product</label>
                                                <div class="col-sm-8">
                                                    <p class="form-control-static">{{ $product->name }}</p>
                                                </div>
                                            </div>
                                            <div class="form-group {{ $errors->has('movement_effective_at') ? 'has-error' : '' }}">
                                                <label class="control-label col-sm-4">Date</label>
                                                <div class="col-sm-8">
                                                    <input type="text" name="movement_effective_at" class="form-control datepicker" value="{{ old('movement_effective_at') ?: $defaultMovementDate->toDateString() }}" required/>
                                                    @foreach($errors->get('movement_effective_at') as $error)
                                                        <span class="label label-danger">{{ $error }}</span>
                                                    @endforeach
                                                </div>
                                            </div>
                                            <div class="form-group {{ $errors->has('cost') ? 'has-error' : '' }}">
                                                <label class="control-label col-sm-4">Cost / {{ $product->isBulkContainer() ? 'Container' : 'Item' }}</label>
                                                <div class="col-sm-8">
                                                    <input type="text" name="cost" class="form-control" value="{{ old('cost') }}" placeholder="Eg: 10000" required/>
                                                    @foreach($errors->get('cost') as $error)
                                                        <span class="label label-danger">{{ $error }}</span>
                                                    @endforeach
                                                </div>
                                            </div>
                                            <div class="form-group {{ $errors->has('quantity') ? 'has-error' : '' }}">
                                                <label class="control-label col-sm-4">Quantity</label>
                                                <div class="col-sm-8">
                                                    <input type="text" name="quantity" class="form-control" value="{{ old('quantity') }}" placeholder="Eg: 12" required/>
                                                    @foreach($errors->get('quantity') as $error)
                                                        <span class="label label-danger">{{ $error }}</span>
                                                    @endforeach
                                                </div>
                                            </div>
                                            <div class="form-group {{ $errors->has('expire_date') ? 'has-error' : '' }}">
                                                <label class="control-label col-sm-4">Expire Date</label>
                                                <div class="col-sm-8">
                                                    <input type="text" name="expire_date" class="form-control datepicker" value="{{ old('expire_date') ?: $defaultExpiredDate->toDateString() }}" required/>
                                                    @foreach($errors->get('expire_date') as $error)
                                                        <span class="label label-danger">{{ $error }}</span>
                                                    @endforeach
                                                </div>
                                            </div>
                                            <div class="form-group {{ $errors->has('expiry_reminder_date') ? 'has-error' : '' }}">
                                                <label class="control-label col-sm-4">Expiry Reminder Date</label>
                                                <div class="col-sm-8">
                                                    <input type="text" name="expiry_reminder_date" class="form-control datepicker" value="{{ old('expiry_reminder_date') ?: $defaultExpiryReminderDate->toDateString() }}" required/>
                                                    @foreach($errors->get('expiry_reminder_date') as $error)
                                                        <span class="label label-danger">{{ $error }}</span>
                                                    @endforeach
                                                </div>
                                            </div>
                                            <div class="form-group {{ $errors->has('remark') ? 'has-error' : '' }}">
                                                <label class="control-label col-sm-4">Remark</label>
                                                <div class="col-sm-8">
                                                    <textarea class="form-control" name="remark">{{ old('remark') }}</textarea>
                                                    @foreach($errors->get('remark') as $error)
                                                        <span class="label label-danger">{{ $error }}</span>
                                                    @endforeach
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-default" data-dismiss="modal">
                                        <i class="fa fa-times fa-fw"></i>
                                        Close
                                    </button>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fa fa-floppy-o fa-fw"></i>
                                        Save
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            @endif
        @endcan
    </div>
@endsection

@section('scripts')
    @parent
    @if(!$product->is_service)
        <script type="text/javascript">
            const app = new Vue({
                el: "#app",
                data: {
                    selectedBranchId: null,
                    selectedBranch: {
                        inventories: [{items: []}]
                    },
                    branches: {!! json_encode($branchInventoryArray) !!}
                },
                watch: {
                    selectedBranchId: function (newValue) {
                        var $this = this;

                        this.branches.forEach((branch) => { if (newValue == branch.id) $this.selectedBranch = branch; });
                    }
                }
            });

            $(document).ready(function () {
                var $movementEffectiveAt = $("input[name='movement_effective_at']"),
                    $expireDate = $("input[name='expire_date']"),
                    $expiryReminderDate = $("input[name='expiry_reminder_date']"),
                    $moveInventoryModal = $("#move-inventory-modal"),
                    $removeInventoryModal = $("#remove-inventory-modal"),
                    $addInventoryModal = $("#add-inventory-modal"),
                    defaultMovementDate = moment("{{ $defaultMovementDate->toDateString() }}"),
                    defaultExpiredDate = moment("{{ $defaultExpiredDate->toDateString() }}");

                $movementEffectiveAt.datepicker("setEndDate", defaultMovementDate.toDate());
                $expireDate.datepicker("setStartDate", defaultMovementDate.add(1, "days").toDate());
                $expiryReminderDate.datepicker("setStartDate", defaultMovementDate.add(1, "days").toDate());
                $expiryReminderDate.datepicker("setEndDate", defaultExpiredDate.subtract(1, "days").toDate());

                $movementEffectiveAt.on("changeDate", function (e) {
                    $expireDate.datepicker("setStartDate", moment(e.date).add(7, 'days').toDate());
                    $expiryReminderDate.datepicker("setStartDate", moment(e.date).add(7, 'days').toDate());
                });

                $expireDate.on("changeDate", function (e) {
                    $expiryReminderDate.datepicker("setEndDate", e.date);
                });

                @if(Session::get('previous_url') === route('products.inventory.add', $product->id) && Session::has('errors'))
                    $addInventoryModal.modal("show");
                @elseif(Session::get('previous_url') === route('products.inventory.move', $product->id) && Session::has('errors'))
                    $moveInventoryModal.modal("show");
                @elseif(Session::get('previous_url') === route('products.inventory.remove', $product->id) && Session::has('errors'))
                    $removeInventoryModal.modal("show");
                @endif
            });
        </script>
    @endif
@endsection