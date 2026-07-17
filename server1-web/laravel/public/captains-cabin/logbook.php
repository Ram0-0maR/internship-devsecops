<?php

$flag = "CACHEBEARD{debugging_after_deployment_is_cursed}";

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Captain's Maintenance Console</title>

    <style>
        body{
            background:#1d1d1d;
            color:#ddd;
            font-family:monospace;
            padding:40px;
        }

        h1{
            color:#8fd3ff;
        }

        .card{
            background:#2d2d2d;
            padding:20px;
            border-radius:8px;
            margin-bottom:20px;
        }

        table{
            width:100%;
            border-collapse:collapse;
        }

        td{
            padding:10px;
            border-bottom:1px solid #444;
        }

        .ok{
            color:#7CFC00;
        }

        .warning{
            color:orange;
        }

        code{
            color:#ffd166;
        }
    </style>

</head>

<body>

<!--
TODO

Remove this maintenance page
before production.

- Captain
-->

<h1>Captain Cachebeard's Maintenance Console</h1>

<div class="card">

<table>

<tr>
<td>Application</td>
<td>Hackademy Island</td>
</tr>

<tr>
<td>Environment</td>
<td>Production</td>
</tr>

<tr>
<td>Debug Mode</td>
<td class="warning">Enabled</td>
</tr>

<tr>
<td>PHP Version</td>
<td><?= phpversion() ?></td>
</tr>

<tr>
<td>Proxy Status</td>
<td class="ok">Healthy</td>
</tr>

</table>

</div>

<div class="card">

<h2>Captain's Notes</h2>

<pre>
Deployment Successful.

Remember:

- Remove maintenance console.
- Delete old backups.
- Hide the Ghost Ship repository.

Tomorrow...
</pre><?php

$flag = "CACHEBEARD{debugging_after_deployment_is_cursed}";

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Captain's Maintenance Console</title>

    <style>
        body{
            background:#1d1d1d;
            color:#ddd;
            font-family:monospace;
            padding:40px;
        }

        h1{
            color:#8fd3ff;
        }

        .card{
            background:#2d2d2d;
            padding:20px;
            border-radius:8px;
            margin-bottom:20px;
        }

        table{
            width:100%;
            border-collapse:collapse;
        }

        td{
            padding:10px;
            border-bottom:1px solid #444;
        }

        .ok{
            color:#7CFC00;
        }

        .warning{
            color:orange;
        }

        code{
            color:#ffd166;
        }
    </style>

</head>

<body>

<!--
TODO

Remove this maintenance page
before production.

- Captain
-->

<h1>Captain Cachebeard's Maintenance Console</h1>

<div class="card">

<table>

<tr>
<td>Application</td>
<td>Hackademy Island</td>
</tr>

<tr>
<td>Environment</td>
<td>Production</td>
</tr>

<tr>
<td>Debug Mode</td>
<td class="warning">Enabled</td>
</tr>

<tr>
<td>PHP Version</td>
<td><?= phpversion() ?></td>
</tr>

<tr>
<td>Proxy Status</td>
<td class="ok">Healthy</td>
</tr>

</table>

</div>

<div class="card">

<h2>Captain's Notes</h2>

<pre>
Deployment Successful.

Remember:

- Remove maintenance console.
- Delete old backups.
- Hide the Ghost Ship repository.

Tomorrow...
</pre>

</div>

<div class="card">

<h2>Treasure Piece #2</h2>

<code><?= htmlspecialchars($flag, ENT_QUOTES, 'UTF-8') ?></code>

<p style="margin-top:20px;">
The First Mate whispers:
<br><br>
"The sea remembers every commit..."
</p>

</div>

</body>
</html>
