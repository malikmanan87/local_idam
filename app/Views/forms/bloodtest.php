<?= $this->extend('layout/page_layout') ?>
<?= $this->section('content') ?>

<div id="layoutSidenav_content">
    <main>
        <div class="container-fluid px-4">
            <h1 class="mt-4">Registration Form test</h1>
            <ol class="breadcrumb mb-4">
                <li class="breadcrumb-item"><a href="/">Dashboard</a></li>
                <li class="breadcrumb-item active">Registration Form</li>
            </ol>

            <div class="col-md-12">
                <div class="card shadow-lg border-0 rounded-4">
                    <div class="card-header bg-danger text-white text-center rounded-top-4">
                        <h4 class="mb-0">🩸 Blood Disposal Registration</h4>
                    </div>

                    <div class="card-body p-4">
                        <form action="<?= base_url('blood/save') ?>" method="post">
                            <?= csrf_field() ?>

                            <!-- Units -->
                            <div class="mb-3 d-flex justify-content-center">
                                <div style="width: 300px;">
                                    <label for="units" class="form-label fw-semibold text-center d-block">
                                        Units
                                    </label>

                                    <select class="form-select text-center" id="units" name="units" required>
                                        <option value="" disabled selected>Select Unit</option>

                                        <?php foreach ($modules as $module): ?>
                                            <option value="<?= esc($module['units_name']) ?>">
                                                <?= esc($module['units_name']) ?>
                                            </option>
                                        <?php endforeach; ?>

                                    </select>
                                </div>
                            </div>


                            <!-- Duration Buttons -->
                            <div id="durationButtons" class="text-center mt-3" style="display: none;">
                                <label class="form-label fw-semibold d-block mb-2">Select Duration</label>
                                <div class="btn-group" role="group">
                                    <input type="radio" class="btn-check" name="duration" id="days7" value="7 Days Chiller" required>
                                    <label class="btn btn-outline-danger px-4" for="days7">7 Days (Chiller)</label>

                                    <input type="radio" class="btn-check" name="duration" id="days7_chiller2" value="7 Days Chiller 2" required>
                                    <label class="btn btn-outline-danger px-4" for="days7_chiller2">7 Days (Chiller 2)</label>

                                    <input type="radio" class="btn-check" name="duration" id="days21" value="21 Days Freezer" required>
                                    <label class="btn btn-outline-danger px-4" for="days21">21 Days (Freezer)</label>

                                    <input type="radio" class="btn-check" name="duration" id="days21_freezer2" value="21 Days Freezer 2" required>
                                    <label class="btn btn-outline-danger px-4" for="days21_freezer2">21 Days (Freezer 2)</label>
                                </div>
                            </div>

                            <!-- Container Buttons -->
                            <div id="containerButtons" class="text-center mt-3" style="display: none;" data-today="<?= date('l') ?>">
                                <label id="containerLabel" class="form-label fw-semibold d-block mb-2">Container</label>

                                <?php
                                $days = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
                                $containers = [
                                    'Chiller' => ['Sunday' => 20, 'Monday' => 15, 'Tuesday' => 25, 'Wednesday' => 100, 'Thursday' => 30, 'Friday' => 10, 'Saturday' => 22],
                                    'Freezer' => ['Sunday' => 10, 'Monday' => 12, 'Tuesday' => 15, 'Wednesday' => 8, 'Thursday' => 20, 'Friday' => 5, 'Saturday' => 18]
                                ];
                                ?>

                                <?php foreach ($days as $day): ?>
                                    <!-- Chiller 1 -->
                                    <input type="radio" class="btn-check container-radio" name="container" id="chiller-<?= strtolower($day) ?>" value="<?= $day ?>" data-type="Chiller" data-day="<?= $day ?>" data-free="<?= $containers['Chiller'][$day] ?>">
                                    <label class="btn container-label chiller px-4 mb-2 me-2" for="chiller-<?= strtolower($day) ?>" data-type="Chiller" data-day="<?= $day ?>">
                                        Chiller <?= $day ?><br>
                                        <small>Free slots: <?= $containers['Chiller'][$day] ?></small><br>
                                        <small class="text-danger fw-bold disposed-label">Disposed on: <?= date('d/m/Y', strtotime($day . ' +7 days')) ?></small>
                                    </label>

                                    <!-- Chiller 2 -->
                                    <input type="radio" class="btn-check container-radio" name="container" id="chiller2-<?= strtolower($day) ?>" value="<?= $day ?>" data-type="Chiller 2" data-day="<?= $day ?>" data-free="<?= $containers['Chiller'][$day] ?>">
                                    <label class="btn container-label chiller px-4 mb-2 me-2" for="chiller2-<?= strtolower($day) ?>" data-type="Chiller 2" data-day="<?= $day ?>">
                                        Chiller 2 <?= $day ?><br>
                                        <small>Free slots: <?= $containers['Chiller'][$day] ?></small><br>
                                        <small class="text-danger fw-bold disposed-label">Disposed on: <?= date('d/m/Y', strtotime($day . ' +7 days')) ?></small>
                                    </label>

                                    <!-- Freezer 1 -->
                                    <input type="radio" class="btn-check container-radio" name="container" id="freezer-<?= strtolower($day) ?>" value="<?= $day ?>" data-type="Freezer" data-day="<?= $day ?>" data-free="<?= $containers['Freezer'][$day] ?>">
                                    <label class="btn container-label freezer px-4 mb-2 me-2" for="freezer-<?= strtolower($day) ?>" data-type="Freezer" data-day="<?= $day ?>">
                                        Freezer <?= $day ?><br>
                                        <small>Free slots: <?= $containers['Freezer'][$day] ?></small><br>
                                        <small class="text-danger fw-bold disposed-label">Disposed on: <?= date('d/m/Y', strtotime($day . ' +14 days')) ?>, <?= date('d/m/Y', strtotime($day . ' +21 days')) ?></small>
                                    </label>

                                    <!-- Freezer 2 -->
                                    <input type="radio" class="btn-check container-radio" name="container" id="freezer2-<?= strtolower($day) ?>" value="<?= $day ?>" data-type="Freezer 2" data-day="<?= $day ?>" data-free="<?= $containers['Freezer'][$day] ?>">
                                    <label class="btn container-label freezer px-4 mb-2 me-2" for="freezer2-<?= strtolower($day) ?>" data-type="Freezer 2" data-day="<?= $day ?>">
                                        Freezer 2 <?= $day ?><br>
                                        <small>Free slots: <?= $containers['Freezer'][$day] ?></small><br>
                                        <small class="text-danger fw-bold disposed-label">Disposed on: <?= date('d/m/Y', strtotime($day . ' +14 days')) ?>, <?= date('d/m/Y', strtotime($day . ' +21 days')) ?></small>
                                    </label>
                                <?php endforeach; ?>
                            </div>

                            <!-- Specimen Code Field -->
                            <div class="mb-3 mt-4 d-flex justify-content-center" id="specimenDiv" style="display: none;">
                                <div style="width: 300px;">
                                    <label for="specimen_code" class="form-label fw-semibold text-center d-block">Specimen Code</label>
                                    <input type="text" class="form-control text-center" id="specimen_code" name="specimen_code" placeholder="Enter specimen code" required>
                                </div>
                            </div>

                            <!-- Submit / Reset Buttons -->
                            <div class="text-center mt-4">
                                <button type="submit" class="btn btn-danger px-4">Register</button>
                                <button type="reset" class="btn btn-secondary px-4 ms-2">Reset</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <footer class="py-4 bg-light mt-auto">
        <div class="container-fluid px-4">
            <div class="d-flex align-items-center justify-content-between small">
                <div class="text-muted">Copyright &copy; Your Website 2023</div>
                <div>
                    <a href="#">Privacy Policy</a> &middot; <a href="#">Terms &amp; Conditions</a>
                </div>
            </div>
        </div>
    </footer>
</div>

<style>
    .container-label.disabled {
        opacity: 0.5;
        pointer-events: none;
        cursor: not-allowed;
    }

    .container-label.active {
        color: #fff !important;
    }

    .container-label.chiller,
    .container-label.freezer {
        background-color: #cce5ff;
        border-color: #66b0ff;
        color: #000;
    }

    .container-label.chiller.active,
    .container-label.freezer.active {
        background-color: #66b0ff;
        color: #fff;
    }
</style>

<script>
    const unitsSelect = document.getElementById('units');
    const durationDiv = document.getElementById('durationButtons');
    const durationRadios = document.querySelectorAll('input[name="duration"]');
    const containerLabels = document.querySelectorAll('.container-label');
    const form = document.querySelector('form');
    const containerDiv = document.getElementById('containerButtons');
    const today = containerDiv.dataset.today.toLowerCase();
    const specimenDiv = document.getElementById('specimenDiv');

    function resetContainers() {
        containerLabels.forEach(label => {
            const input = document.getElementById(label.htmlFor);
            input.checked = false;
            input.disabled = true;
            label.classList.remove('active', 'disabled');
        });
    }

    function updateContainers() {
        const selectedDuration = document.querySelector('input[name="duration"]:checked')?.value;
        if (!selectedDuration) {
            specimenDiv.style.display = 'none';
            return;
        }

        let type = '';
        if (selectedDuration === '7 Days Chiller') type = 'Chiller';
        if (selectedDuration === '7 Days Chiller 2') type = 'Chiller 2';
        if (selectedDuration === '21 Days Freezer') type = 'Freezer';
        if (selectedDuration === '21 Days Freezer 2') type = 'Freezer 2';

        containerLabels.forEach(label => {
            const input = document.getElementById(label.htmlFor);
            const labelType = label.dataset.type;
            const labelDay = label.dataset.day.toLowerCase();

            if (labelType === type) {
                label.style.display = 'inline-block';
                if (labelDay === today) {
                    input.disabled = false;
                    input.checked = true;
                    label.classList.add('active');
                    label.classList.remove('disabled');
                } else {
                    input.disabled = true;
                    input.checked = false;
                    label.classList.remove('active');
                    label.classList.add('disabled');
                }
            } else {
                label.style.display = 'none';
                input.disabled = true;
                input.checked = false;
                label.classList.remove('active', 'disabled');
            }
        });

        specimenDiv.style.display = 'block';
        document.getElementById('specimen_code').focus();
    }

    unitsSelect.addEventListener('change', () => {
        if (unitsSelect.value) {
            durationDiv.style.display = 'block';
            containerDiv.style.display = 'block';
            specimenDiv.style.display = 'none';
            durationRadios.forEach(r => r.checked = false);
            resetContainers();
            containerDiv.scrollIntoView({
                behavior: 'smooth'
            });
        } else {
            durationDiv.style.display = 'none';
            containerDiv.style.display = 'none';
            specimenDiv.style.display = 'none';
            resetContainers();
        }
    });

    durationRadios.forEach(radio => radio.addEventListener('change', updateContainers));
    form.addEventListener('reset', () => {
        durationDiv.style.display = 'none';
        containerDiv.style.display = 'none';
        specimenDiv.style.display = 'none';
        unitsSelect.value = '';
        resetContainers();
    });
</script>

<?= $this->endSection() ?>