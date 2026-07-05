{{--
    Bảng cây (tree table) dùng chung cho 3 loại BCTC (CĐKT/KQKD/LCTT).
    Mỗi StatementItem đã mang sẵn level/parent_id/expanded từ external API — tận dụng để:
      - Phân lớp bằng thụt lề + trọng lượng chữ theo $item->level.
      - Đề mục có con (hasChildren) hiển thị đậm + có nút thu gọn/mở rộng (dựa theo
        $item->expanded mặc định của API: true = hiện con, false = ẩn con).
      - Dòng gốc (level 1) và dòng tổng (không có con, tên bắt đầu bằng "Tổng/Cộng/…")
        được tô đậm + màu theo dấu giá trị để dễ nhận ra "mốc" quan trọng.
    Cần: $items (Collection<StatementItem>), $year, $quarter, $treeId (id duy nhất cho bảng).
--}}
@php
    $itemsArr = $items->values();
    $expandedOf = [];
    $childOf = [];
    $visible = [];
    foreach ($itemsArr as $it) {
        $expandedOf[$it->id] = (bool) $it->expanded;
        if ($it->parent_id != -1) {
            $childOf[$it->parent_id] = true;
        }
        $visible[$it->id] = ($it->parent_id == -1 || !array_key_exists($it->parent_id, $visible))
            ? true
            : ($visible[$it->parent_id] && ($expandedOf[$it->parent_id] ?? true));
    }
@endphp
<table class="table table-hover table-sm ks-statement-table ks-tree-table" data-tree-table="{{ $treeId }}">
    <thead>
        <tr>
            @if(config('settings.display_statement_item_code') == 'on')
                <th style="width:70px">Code</th>
            @endif
            <th>Chỉ tiêu</th>
            <th class="text-right" style="width:160px">Giá trị (Tỷ VND)</th>
        </tr>
    </thead>
    <tbody>
        @foreach($itemsArr as $item)
            @php
                $level = max(1, (int) $item->level);
                $hasChildren = isset($childOf[$item->id]);
                $rawName = trim($item->name, "- \t");
                $value = $item->getValue($year, $quarter);
                $displayValue = readVietnameseDongForHuman($value);
                $isTotalByName = (bool) preg_match('/^(tổng|cộng|lưu chuyển tiền thuần)/iu', $rawName);
                $isHeadline = $hasChildren || $level === 1 || $isTotalByName;
            @endphp
            <tr class="ks-stmt-row ks-stmt-lvl-{{ min($level, 5) }} {{ $isHeadline ? 'ks-stmt-headline' : '' }}"
                data-id="{{ $item->id }}" data-parent="{{ $item->parent_id }}"
                style="{{ $visible[$item->id] ? '' : 'display:none' }}">
                @if(config('settings.display_statement_item_code') == 'on')
                    <td class="text-muted small">{{ $item->id }}</td>
                @endif
                <td class="ks-stmt-name" style="padding-left: {{ ($level - 1) * 1.35 }}rem">
                    @if($hasChildren)
                        <button type="button" class="ks-stmt-toggle" data-node="{{ $item->id }}"
                                data-expanded="{{ $expandedOf[$item->id] ? '1' : '0' }}" aria-label="Thu gọn/Mở rộng">
                            <i class="fas fa-chevron-{{ $expandedOf[$item->id] ? 'down' : 'right' }}"></i>
                        </button>
                    @endif
                    <span>{{ $rawName }}</span>
                </td>
                <td class="text-right ks-stmt-value
                    {{ $displayValue !== null && $displayValue < 0 ? 'ks-stmt-val-neg' : '' }}
                    {{ $isHeadline && $displayValue !== null && $displayValue > 0 ? 'ks-stmt-val-pos' : '' }}">
                    {{ $displayValue !== null ? number_format($displayValue, 2) : '—' }}
                </td>
            </tr>
        @endforeach
    </tbody>
</table>
