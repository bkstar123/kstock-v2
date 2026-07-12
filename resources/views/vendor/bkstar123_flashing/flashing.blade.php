@if(session()->has('flash_notification'))
@php
    $__flash = session('flash_notification');
    $__type  = $__flash['type'] ?? 'information';
    $__meta  = [
        'success'     => ['icon' => 'fa-check-circle',         'label' => 'Success'],
        'error'       => ['icon' => 'fa-times-circle',         'label' => 'Error'],
        'warning'     => ['icon' => 'fa-exclamation-triangle', 'label' => 'Warning'],
        'information' => ['icon' => 'fa-info-circle',           'label' => 'Information'],
    ][$__type] ?? ['icon' => 'fa-info-circle', 'label' => 'Notice'];
@endphp
<div class="ks-toast-wrap ks-toast-{{ $__flash['position'] ?? 'bottom' }}">
    <div id="bkstar123-flashing-toast"
         class="toast ks-toast ks-toast--{{ $__type }}"
         role="alert" aria-atomic="true" aria-live="polite"
         {!! ($__flash['important'] ?? false)
                ? 'data-autohide="false"'
                : 'data-delay="' . ($__flash['timeout'] ?? 5000) . '"' !!}>
        <span class="ks-toast__accent"></span>
        <span class="ks-toast__icon"><i class="fas {{ $__meta['icon'] }}"></i></span>
        <div class="ks-toast__body">
            <div class="ks-toast__title">{{ $__meta['label'] }}</div>
            <div class="ks-toast__msg">{{ $__flash['message'] }}</div>
        </div>
        <button type="button" class="ks-toast__close" data-dismiss="toast" aria-label="Close">&times;</button>
    </div>
</div>
<script>
    (function () {
        function showFlash() {
            var $t = window.jQuery && jQuery('#bkstar123-flashing-toast');
            if ($t && $t.length && jQuery.fn.toast) { $t.toast('show'); }
        }
        if (window.jQuery) { jQuery(showFlash); } else { document.addEventListener('DOMContentLoaded', showFlash); }
    })();
</script>
@endif
