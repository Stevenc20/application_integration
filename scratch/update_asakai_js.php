<?php

$file = 'resources/views/reports/asakai.blade.php';
$content = file_get_contents($file);

$js = <<< 'HTML'

@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Dynamic diff coloring for elements with dynamic-diff class
        const diffCells = document.querySelectorAll('.dynamic-diff');
        
        function updateColor(cell) {
            const val = parseFloat(cell.innerText.replace(/[^0-9.-]/g, ''));
            if (!isNaN(val)) {
                if (val < 0) {
                    cell.classList.add('diff-negative');
                    cell.classList.remove('diff-positive');
                } else if (val > 0) {
                    cell.classList.add('diff-positive');
                    cell.classList.remove('diff-negative');
                } else {
                    cell.classList.remove('diff-negative', 'diff-positive');
                }
            }
        }
        
        diffCells.forEach(cell => {
            // Apply initial color
            updateColor(cell);
            
            // Listen for changes
            cell.addEventListener('input', function() {
                updateColor(cell);
            });
        });
    });
</script>
@endsection
HTML;

$content = str_replace("@endsection\n", "@endsection\n" . $js, $content);
file_put_contents($file, $content);
echo "Added JS scripts!";
