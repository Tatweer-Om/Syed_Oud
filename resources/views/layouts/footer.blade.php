  <footer class="bg-white dark:bg-gray-900 text-center text-gray-600 dark:text-gray-400 py-3 text-sm border-t border-gray-100 dark:border-gray-800">
          جميع الحقوق محفوظة © صنع بحب من قِبل تطوير <span class="text-red-500">❤</span>
        </footer>

<!-- Bootstrap JS bundle (includes Popper.js) -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

         <script src="{{asset('js/custom.js')}}"></script>
         <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.20/dist/sweetalert2.all.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
@include('custom_js.custom_js')
@php

    $routeName = Route::currentRouteName();
    $segments = explode('.', $routeName);
    $route_name = isset($segments[0]) ? $segments[0] : null;

@endphp

    @if ($route_name == 'size')
         @include('custom_js.size_js')
         @elseif ($route_name == 'color')
         @include('custom_js.color_js')
          @elseif ($route_name == 'channel')
         @include('custom_js.channel_js')
          @elseif ($route_name == 'category')
         @include('custom_js.category_js')
             @elseif ($route_name == 'tailor_profile')
         @include('custom_js.tailor_profile_js')
          @elseif ($route_name == 'tailor')
         @include('custom_js.tailor_js')
            @elseif ($route_name == 'user')
         @include('custom_js.user_js')
   @elseif ($route_name == 'boutique')
         @include('custom_js.boutique_js')
  @elseif ($route_name == 'boutique_list')
         @include('custom_js.boutique_list_js')
           @elseif ($route_name == 'edit_boutique')
         @include('custom_js.edit_boutique_js')
           @elseif ($route_name == 'stock')
         @include('custom_js.stock_js')
            @elseif ($route_name == 'view_stock')
         @include('custom_js.stock_list_js')
          @elseif ($routeName == 'stock.audit')
         @include('custom_js.stock_audit_js')
              @elseif ($route_name == 'edit_stock')
         @include('custom_js.edit_stock_js')
          @elseif ($route_name == 'view_material')
         @include('custom_js.material_list_js')
          @elseif ($route_name == 'view_units')
         @include('custom_js.unit_list_js')
          @elseif ($route_name == 'purchase' && $routeName != 'purchase.profile')
         @include('custom_js.purchase_js')
          @elseif ($route_name == 'view_purchase')
         @include('custom_js.view_purchase_js')
         @elseif ($routeName == 'production.profile')
        @include('custom_js.production_profile_js')
         @elseif ($routeName == 'packaging.create')
        @include('custom_js.packaging_js')
         @elseif ($routeName == 'packaging.profile')
        @include('custom_js.packaging_profile_js')
         @elseif (($route_name == 'production' || $routeName == 'production.draft.edit') && $routeName != 'production.profile')
        @include('custom_js.production_js')
          @elseif ($route_name == 'view_production')
         @include('custom_js.view_production_js')
           @elseif ($route_name == 'edit_material')
         @include('custom_js.edit_material_js')
          @elseif ($route_name == 'spcialorder')
         @include('custom_js.special_order_js')
              @elseif ($route_name == 'view_special_order')
         @include('custom_js.view_orders_js')
                  @elseif ($route_name == 'send_request')
         @include('custom_js.send_request_js')
          @elseif ($route_name == 'account')
         @include('custom_js.account_js')
          @elseif ($route_name == 'area')
         @include('custom_js.area_js')
          @elseif ($route_name == 'city')
         @include('custom_js.city_js')
          @elseif ($routeName == 'pos.orders.list')
         @include('custom_js.pos_orders_list_js')
          @elseif ($routeName == 'tailor_orders_list')
         @include('custom_js.tailor_orders_list_js')
          @elseif ($routeName == 'customer_profile')
         @include('custom_js.customer_profile_js')
            @elseif ($route_name == 'pos')
         @include('custom_js.pos_js')
                    @elseif ($route_name == 'bouttique_profile')
         @include('custom_js.boutique_profile_js')
           @elseif ($route_name == 'channel_profile')
         @include('custom_js.channel_profile_js')
           @elseif ($route_name == 'maintenance')
         @include('custom_js.maintenance_js')
              {{-- <!-- @elseif ($route_name == 'manage_quantity') --}}
         {{-- @include('custom_js.manage_quantity_js') --> --}}
          @elseif ($route_name == 'movements_log')
         @include('custom_js.movement_log_js')
          @elseif ($route_name == 'tailor_material_audit')
         @include('custom_js.material_audit_js')
         @elseif ($route_name == 'asset')
         @include('custom_js.asset_js')
         @elseif ($route_name == 'assetsmaintenance')
         @include('custom_js.asset_maintenance_js')
    @endif
          
  </body>
</html>
