<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title inertia>{{ config('app.name', 'Laravel') }}</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />
    {{-- CSS --}}

    <!-- ================== BEGIN core-css ================== -->
    <link href="{{ asset('assets/template/assets/css/vendor.min.css') }}" rel="stylesheet" />
    <link href="{{ asset('assets/template/assets/css/app.min.css') }}" rel="stylesheet" />
    <!-- ================== END core-css ================== -->

    <!-- ================== BEGIN page-css ================== -->

    <link href="{{ asset('assets/template/assets/plugins/switchery/dist/switchery.min.css') }}" rel="stylesheet" />
    <link href="{{ asset('assets/template/assets/plugins/datatables.net-bs5/css/dataTables.bootstrap5.min.css') }}"
        rel="stylesheet" />
    <link
        href="{{ asset('assets/template/assets/plugins/datatables.net-responsive-bs5/css/responsive.bootstrap5.min.css') }}"
        rel="stylesheet" />
    <link href="{{ asset('assets/template/assets/plugins/gritter/css/jquery.gritter.css') }}" rel="stylesheet" />
    <!-- ================== END page-css ================== -->

    <!-- Styles -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/css/select2.min.css" />
    {{-- <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" /> --}}
    <!-- Or for RTL support -->
    <link rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.rtl.min.css" />

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/jquery@3.5.0/dist/jquery.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/js/select2.full.min.js"></script>

    @routes
    @vite(['resources/js/app.js', "resources/js/Pages/{$page['component']}.vue"])
    @inertiaHead
</head>

<body class="app-blank">
    @inertia

    <!-- ================== BEGIN core-js ================== -->
    <script src="{{ asset('assets/template/assets/js/vendor.min.js') }}"></script>
    {{-- <script src="{{ asset('assets/template/assets/js/app.js')}}"></script> --}}
    <!-- ================== END core-js ================== -->

    <!-- ================== BEGIN page-js ================== -->
    <script src="{{ asset('assets/template/assets/plugins/datatables.net/js/dataTables.min.js') }}"></script>
    <script src="{{ asset('assets/template/assets/plugins/datatables.net-bs5/js/dataTables.bootstrap5.min.js') }}">
    </script>
    <script src="{{ asset('assets/template/assets/plugins/datatables.net-responsive/js/dataTables.responsive.min.js') }}">
    </script>
    <script
        src="{{ asset('assets/template/assets/plugins/datatables.net-responsive-bs5/js/responsive.bootstrap5.min.js') }}">
    </script>
    <script src="{{ asset('assets/template/assets/js/demo/table-manage-responsive.demo.js') }}"></script>
    <script src="{{ asset('assets/template/assets/plugins/switchery/dist/switchery.min.js') }}"></script>
    <script src="{{ asset('assets/template/assets/plugins/gritter/js/jquery.gritter.js') }}"></script>
    <script src="{{ asset('assets/template/assets/js/demo/ui-modal-notification.demo.js') }}"></script>
    <script src="{{ asset('assets/template/assets/plugins/@highlightjs/cdn-assets/highlight.min.js') }}"></script>
    <!-- ================== END page-js ================== -->
</body>

</html>
