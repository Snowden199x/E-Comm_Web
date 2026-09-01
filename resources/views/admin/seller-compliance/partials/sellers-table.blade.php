<div class="bg-white rounded-2xl p-5 shadow-sm overflow-x-auto">
      <table class="w-full text-sm">
          <thead>
              <tr class="text-left text-gray-500 border-b">
                  <th class="pb-3 pr-4 font-medium">Seller</th>
                  <th class="pb-3 px-4 font-medium">Category</th>
                  <th class="pb-3 px-4 font-medium">Compliance Score</th>
                  <th class="pb-3 px-4 font-medium text-center">Warnings</th>
                  <th class="pb-3 px-4 font-medium text-center">Violations</th>
                  <th class="pb-3 px-4 font-medium">Status</th>
              </tr>
          </thead>
          <tbody>
              @forelse ($sellers as $seller)
                  <tr class="border-b last:border-0">
                      <td class="py-3 flex items-center gap-3">
                          <div
                              class="w-8 h-8 rounded-full bg-gray-200 flex items-center justify-center text-xs font-semibold text-gray-600">
                              {{ strtoupper(substr($seller->name, 0, 1)) }}
                          </div>
                          <span class="text-gray-900">{{ $seller->name }}</span>
                      </td>
                      <td class="py-3">
                          @if ($seller->categories->isNotEmpty())
                              <span class="px-3 py-1 rounded-full bg-blue-50 text-blue-700 text-xs font-medium">
                                  {{ $seller->categories->pluck('name')->join(' & ') }}
                              </span>
                          @endif
                      </td>
                      <td class="py-3 w-40">
                          <div class="flex items-center gap-2">
                              <div class="flex-1 h-1.5 bg-gray-100 rounded-full overflow-hidden">
                                  <div class="h-full bg-green-500" style="width: {{ $seller->compliance_score }}%">
                                  </div>
                              </div>
                              <span class="text-gray-700 text-xs">{{ $seller->compliance_score }}%</span>
                          </div>
                      </td>
                      <td class="py-3 px-4 text-red-500 font-medium text-center">
                          {{ $seller->product_warnings_count }}</td>
                      <td class="py-3 px-4 text-red-700 font-medium text-center">
                          {{ $seller->product_violations_count }}</td>
                      <td class="py-3">
                          <span @class([
                              'px-2 py-1 rounded-full text-xs font-medium',
                              'bg-green-100 text-green-700' => $seller->status === 'approved',
                              'bg-red-100 text-red-700' => $seller->status === 'suspended',
                          ])>
                              {{ $seller->status === 'approved' ? 'Compliant' : ucfirst($seller->status) }}
                          </span>
                      </td>
                  </tr>
              @empty
                  <tr>
                      <td colspan="6" class="py-8 text-center text-gray-400">No sellers found.</td>
                  </tr>
              @endforelse
          </tbody>
      </table>
      @if ($sellers->hasPages())
          <div class="flex items-center justify-between mt-4 pt-4 border-t">
              <p class="text-xs text-gray-500">Showing {{ $sellers->count() }} out of {{ $sellers->total() }}
                  entries</p>
              <div>{{ $sellers->links() }}</div>
          </div>
      @endif
</div>