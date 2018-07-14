<?php

DeleteClientCronjobs();
WriteCronFile();

function WriteCronFile() {
    global $zdbh;
    $line = "";
    $sql = "SELECT * FROM x_cronjobs WHERE ct_deleted_ts IS NULL";
    $numrows = $zdbh->query($sql);
    if ($numrows->fetchColumn() <> 0) {
        $sql = $zdbh->prepare($sql);
        $sql->execute();
        $line .= "#################################################################################" . PHP_EOL;
        $line .= "# CRONTAB FOR SENTORA CRON MANAGER MODULE                                        " . PHP_EOL;
        $line .= "# Module Developed by Bobby Allen, 17/12/2009                                    " . PHP_EOL;
        $line .= "# Automatically generated by Sentora " . sys_versions::ShowSentoraVersion() . "     " . PHP_EOL;
        $line .= "#################################################################################" . PHP_EOL;
        $line .= "# WE DO NOT RECOMMEND YOU MODIFY THIS FILE DIRECTLY, PLEASE USE SENTORA INSTEAD! " . PHP_EOL;
        $line .= "#################################################################################" . PHP_EOL;

        if (sys_versions::ShowOSPlatformVersion() == "Windows") {
            $line .= "# Cron Debug infomation can be found in this file here:-                        " . PHP_EOL;
            $line .= "# C:\WINDOWS\System32\crontab.txt                                                " . PHP_EOL;
            $line .= "#################################################################################" . PHP_EOL;
            $line .= "" . ctrl_options::GetSystemOption('daemon_timing') . " " . ctrl_options::GetSystemOption('php_exer') . " " . ctrl_options::GetSystemOption('daemon_exer') . "" . PHP_EOL;
            $line .= "#################################################################################" . PHP_EOL;
        }

        $line .= "# DO NOT MANUALLY REMOVE ANY OF THE CRON ENTRIES FROM THIS FILE, USE SENTORA     " . PHP_EOL;
        $line .= "# INSTEAD! THE ABOVE ENTRIES ARE USED FOR SENTORA TASKS, DO NOT REMOVE THEM!     " . PHP_EOL;
        $line .= "#################################################################################" . PHP_EOL;
        while ($rowcron = $sql->fetch()) {
            //$rowclient = $zdbh->query("SELECT * FROM x_accounts WHERE ac_id_pk=" . $rowcron['ct_acc_fk'] . " AND ac_deleted_ts IS NULL")->fetch();
            $numrows = $zdbh->prepare("SELECT * FROM x_accounts WHERE ac_id_pk=:userid AND ac_deleted_ts IS NULL");
            $numrows->bindParam(':userid', $rowcron['ct_acc_fk']);
            $numrows->execute();
            $rowclient = $numrows->fetch();
            
            if ($rowclient && $rowclient['ac_enabled_in'] <> 0) {
                $line .= "# CRON ID: " . $rowcron['ct_id_pk'] . "" . PHP_EOL;
                $line .= "" . $rowcron['ct_timing_vc'] . " " . ctrl_options::GetSystemOption('php_exer') . " " . $rowcron['ct_fullpath_vc'] . "" . PHP_EOL;
                $line .= "# END CRON ID: " . $rowcron['ct_id_pk'] . "" . PHP_EOL;
            }
        }

        if (fs_filehandler::UpdateFile(ctrl_options::GetSystemOption('cron_file'), 0777, $line)) {
            return true;
        } else {
            return false;
        }
    }
}

function DeleteClientCronjobs() {
    global $zdbh;
    $sql = "SELECT * FROM x_accounts WHERE ac_deleted_ts IS NOT NULL";
    $numrows = $zdbh->query($sql);
    if ($numrows->fetchColumn() <> 0) {
        $sql = $zdbh->prepare($sql);
        $sql->execute();
        while ($rowclient = $sql->fetch()) {
            //$rowcron = $zdbh->query("SELECT * FROM x_cronjobs WHERE ct_acc_fk=" . $rowclient['ac_id_pk'] . " AND ct_deleted_ts IS NULL")->fetch();
            $numrows = $zdbh->prepare("SELECT * FROM x_cronjobs WHERE ct_acc_fk=:userid AND ct_deleted_ts IS NULL");
            $numrows->bindParam(':userid', $rowclient['ac_id_pk']);
            $numrows->execute();
            $rowcron = $numrows->fetch();
            
            if ($rowcron) {
                $delete = "UPDATE x_cronjobs SET ct_deleted_ts=:time WHERE ct_acc_fk=:userid";
                $delete = $zdbh->prepare($delete);
                $delete->bindParam(':time', time());
                $delete->bindParam(':userid', $rowclient['ac_id_pk']);
                $delete->execute();
            }
        }
    }
}

?>