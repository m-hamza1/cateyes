    </div><!-- /content-wrapper -->
</main><!-- /main-content -->

    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    
    <!-- Bootstrap Bundle JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- DataTables JS -->
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
    
    <!-- Select2 JS -->
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.js"></script>
    
    <!-- Admin Custom JS -->
    <script>
        $(document).ready(function() {
            const sidebar = document.getElementById('sidebarMenu');
            const sidebarBackdrop = document.getElementById('adminSidebarBackdrop');
            const mobileBreakpoint = window.matchMedia('(max-width: 767.98px)');

            function syncSidebarBackdrop(isVisible) {
                if (!sidebarBackdrop) {
                    return;
                }

                sidebarBackdrop.classList.toggle('show', !!isVisible && mobileBreakpoint.matches);
                document.body.classList.toggle('admin-sidebar-open', !!isVisible && mobileBreakpoint.matches);
            }

            // If this page was restored from the browser back/forward cache,
            // reload to ensure fresh server-rendered data (badges, counts, etc.)
            window.addEventListener('pageshow', function(event) {
                if (event.persisted) {
                    window.location.reload();
                }
            });

            // Initialize DataTables
            if ($('.datatable').length) {
                $('.datatable').DataTable({
                    "pageLength": 25,
                    "ordering": true,
                    "searching": true,
                    "language": {
                        <?php if ($lang->getDirection() === 'rtl'): ?>
                        "url": "//cdn.datatables.net/plug-ins/1.13.7/i18n/ar.json"
                        <?php endif; ?>
                    }
                });
            }

            // Make tables scroll on smaller screens without changing page structure.
            $('.table').each(function() {
                const $table = $(this);
                if (!$table.parent().hasClass('table-responsive') && !$table.parent().hasClass('admin-table-responsive')) {
                    $table.wrap('<div class="table-responsive admin-table-responsive"></div>');
                }
            });
            
            // Initialize Select2
            if ($('.select2').length) {
                $('.select2').select2({
                    theme: 'bootstrap-5',
                    width: '100%'
                });
            }
            
            // Confirm delete
            $('.btn-delete').on('click', function(e) {
                if (!confirm('Are you sure you want to delete this item?')) {
                    e.preventDefault();
                    return false;
                }
            });
            
            // Auto-hide alerts after 5 seconds
            setTimeout(function() {
                $('.alert').fadeOut('slow');
            }, 5000);
            
            // Image preview
            $('.image-input').on('change', function() {
                const input = this;
                const preview = $(this).data('preview');
                
                if (input.files && input.files[0]) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        $(preview).attr('src', e.target.result).show();
                    };
                    reader.readAsDataURL(input.files[0]);
                }
            });
            
            // Toggle checkboxes
            $('#check-all').on('change', function() {
                $('.item-checkbox').prop('checked', $(this).prop('checked'));
            });
            
            // Bulk actions
            $('#bulk-action-btn').on('click', function() {
                const action = $('#bulk-action').val();
                const selected = [];
                
                $('.item-checkbox:checked').each(function() {
                    selected.push($(this).val());
                });
                
                if (selected.length === 0) {
                    alert('Please select at least one item');
                    return false;
                }
                
                if (action === 'delete') {
                    if (!confirm('Are you sure you want to delete ' + selected.length + ' item(s)?')) {
                        return false;
                    }
                }
                
                // Submit form or AJAX request
                // Implementation depends on specific needs
            });

            if (sidebar && sidebarBackdrop) {
                sidebar.addEventListener('shown.bs.collapse', function() {
                    syncSidebarBackdrop(true);
                });

                sidebar.addEventListener('hidden.bs.collapse', function() {
                    syncSidebarBackdrop(false);
                });

                sidebarBackdrop.addEventListener('click', function() {
                    const sidebarInstance = bootstrap.Collapse.getOrCreateInstance(sidebar, { toggle: false });
                    sidebarInstance.hide();
                });

                $(sidebar).find('.nav-link').on('click', function() {
                    if (!mobileBreakpoint.matches || !sidebar.classList.contains('show')) {
                        return;
                    }

                    const sidebarInstance = bootstrap.Collapse.getOrCreateInstance(sidebar, { toggle: false });
                    sidebarInstance.hide();
                });

                window.addEventListener('resize', function() {
                    if (!mobileBreakpoint.matches) {
                        syncSidebarBackdrop(false);
                    }
                });
            }
        });
        
        // Helper function for AJAX requests
        function ajaxRequest(url, data, successCallback, errorCallback) {
            $.ajax({
                url: url,
                type: 'POST',
                data: data,
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        if (successCallback) successCallback(response);
                    } else {
                        if (errorCallback) {
                            errorCallback(response);
                        } else {
                            alert(response.message || 'An error occurred');
                        }
                    }
                },
                error: function(xhr, status, error) {
                    if (errorCallback) {
                        errorCallback({success: false, message: error});
                    } else {
                        alert('An error occurred: ' + error);
                    }
                }
            });
        }
        
        // Show loading overlay
        function showLoading() {
            $('body').append('<div class="loading-overlay"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div></div>');
        }
        
        // Hide loading overlay
        function hideLoading() {
            $('.loading-overlay').remove();
        }
    </script>
    
    <style>
        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 9999;
        }
    </style>
</body>
</html>
