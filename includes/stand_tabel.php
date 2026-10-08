<?php
// Toont de stand als tabel. Wordt gebruikt op de toernooipagina en in het beheer.
// Verwacht de variabele $stand (uitkomst van bereken_stand()).
?>
<?php if (!$stand): ?>
    <p class="leeg">Er is nog geen stand, omdat er nog geen goedgekeurde deelnemers zijn.</p>
<?php else: ?>
    <div class="tabel-wrapper">
        <table>
            <thead>
            <tr><th>#</th><th>Speler</th><th>Gespeeld</th><th>W</th><th>G</th><th>V</th><th>Punten</th></tr>
            </thead>
            <tbody>
            <?php foreach ($stand as $plek => $regel): ?>
                <tr>
                    <td><?= $plek + 1 ?></td>
                    <td><?= e($regel['spelersnaam']) ?></td>
                    <td><?= $regel['gespeeld'] ?></td>
                    <td><?= $regel['gewonnen'] ?></td>
                    <td><?= $regel['gelijk'] ?></td>
                    <td><?= $regel['verloren'] ?></td>
                    <td><strong><?= $regel['punten'] ?></strong></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>
