# Downloads all third-party libraries into ./vendor so the PDF tool runs FULLY OFFLINE.
# Run from the project folder:  powershell -ExecutionPolicy Bypass -File vendor/download_libs.ps1
$ErrorActionPreference = 'Stop'
$base = Split-Path -Parent $MyInvocation.MyCommand.Path
$vendor = Join-Path $base ''
$tess = Join-Path $vendor 'tesseract'
New-Item -ItemType Directory -Force -Path $vendor | Out-Null
New-Item -ItemType Directory -Force -Path $tess | Out-Null

$files = @(
    @{ url='https://unpkg.com/pdf-lib/dist/pdf-lib.min.js';                                   out=Join-Path $vendor 'pdf-lib.min.js' },
    @{ url='https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js';                out=Join-Path $vendor 'pdf.min.js' },
    @{ url='https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js';          out=Join-Path $vendor 'pdf.worker.min.js' },
    @{ url='https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js';          out=Join-Path $vendor 'sweetalert2.all.min.js' },
    @{ url='https://unpkg.com/tesseract.js@v5.0.3/dist/tesseract.min.js';                      out=Join-Path $tess 'tesseract.min.js' },
    @{ url='https://unpkg.com/tesseract.js@v5.0.3/dist/worker.min.js';                         out=Join-Path $tess 'worker.min.js' },
    @{ url='https://unpkg.com/tesseract.js@v5.0.3/dist/tesseract-core.wasm.js';               out=Join-Path $tess 'tesseract-core.wasm.js' },
    @{ url='https://unpkg.com/tesseract.js@v5.0.3/dist/tesseract-core.wasm';                   out=Join-Path $tess 'tesseract-core.wasm' },
    @{ url='https://raw.githubusercontent.com/naptha/tessdata/master/eng.traineddata.gz';       out=Join-Path $tess 'eng.traineddata.gz' }
)

foreach ($f in $files) {
    Write-Host "Downloading $($f.url)"
    Invoke-WebRequest -Uri $f.url -OutFile $f.out -UseBasicParsing
}
Write-Host "Done. Libraries are in ./vendor. The tool now uses them automatically (offline)."
