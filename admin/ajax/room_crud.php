<?php
require("../inc/essential.php");
require("../inc/dbconfig.php");
admin_login();

if (isset($_POST['add_room'])) {
    $features = filteration(json_decode($_POST['features']));
    $facilities = filteration(json_decode($_POST['facilities']));
    $frm_data = filteration($_POST);

    $q1 = "INSERT INTO `rooms`(`name`, `area`, `price`, `quantity`, `adult`, `children`, `description`) VALUES (?,?,?,?,?,?,?)";
    $values = [$frm_data['name'], $frm_data['area'], $frm_data['price'], $frm_data['quantity'], $frm_data['adult'], $frm_data['children'], $frm_data['desc']];

    if (insert($q1, $values, 'siiiiis')) {
        $flag = 1;
    }

    $roomid = mysqli_insert_id($con);

    $q2 = "INSERT INTO `room_facilities`(`room_id`, `facilities_id`) VALUES (?,?)";

    if ($stmt = mysqli_prepare($con, $q2)) {
        foreach ($facilities as $f) {
            mysqli_stmt_bind_param($stmt, 'ii', $roomid, $f);
            mysqli_execute($stmt);
        }
        mysqli_stmt_close($stmt);
    } else {
        $flag = 0;
        die('querry cannot prepared - INSERT');
    }

    $q3 = "INSERT INTO `room_features`(`room_id`, `features_id`) VALUES (?,?)";

    if ($stmt = mysqli_prepare($con, $q3)) {
        foreach ($features as $fe) {
            mysqli_stmt_bind_param($stmt, 'ii', $roomid, $fe);
            mysqli_execute($stmt);
        }
        mysqli_stmt_close($stmt);
    } else {
        $flag = 0;
        die('querry cannot prepared - INSERT');
    }


    if ($flag == 1) {
        echo 1;
    } else {
        echo 0;
    }
}

if (isset($_POST['getall_rooms'])) {
    $res = select("SELECT * FROM `rooms` WHERE `removed`=?",[0],'i');
    $i = 1;
    $data = '';

    while ($row = mysqli_fetch_assoc($res)) {
        if ($row['status'] == 1) {
            $status = "<button onclick='toggle_status($row[id], 0)' class ='btn btn-sm btn-dark shadow-none'>active</button>";
        } else {
            $status = "<button onclick='toggle_status($row[id], 1)' class ='btn btn-sm btn-warning shadow-none'>Inactive</button>";
        }
        $data .= "
        <tr class = 'align-middle'>
            <td>$i</td>
            <td>$row[name]</td>
            <td>$row[area] sq. ft</td>
            <td>
                <span class = 'badge rounded-pill bg-light text-dark'>
                Adults: $row[adult]
                </span>
                <span class = 'badge rounded-pill bg-light text-dark'>
                Childrens: $row[children]
                </span>
            </td>
            <td>₹$row[price]</td>
            <td>$row[quantity]</td>
            <td>$status</td>
            <td>
                <button type='button' onclick='edit_details($row[id])' class='btn btn-sm btn-primary shadow-none' data-bs-toggle='modal'
                    data-bs-target='#edit-room'>
                    <i class='bi bi-pencil-square'></i>
                </button>
                <button type='button' onclick=\"room_images($row[id],'$row[name]')\" class='btn btn-sm btn-info shadow-none' data-bs-toggle='modal'
                    data-bs-target='#room-img'>
                    <i class='bi bi-images'></i>
                </button>
                <button type='button' onclick='rem_room($row[id])' class='btn btn-sm btn-danger shadow-none'>
                    <i class='bi bi-trash'></i>
                </button>
            </td>
        </tr>
        ";
        $i++;
    }

    echo $data;

}

if (isset($_POST['get_room'])) {
    $frm_data = filteration($_POST);

    $res1 = select("SELECT * FROM `rooms` WHERE `id`=?", [$frm_data['get_room']], 'i');
    $res2 = select("SELECT * FROM `room_features` WHERE `room_id`=?", [$frm_data['get_room']], 'i');
    $res3 = select("SELECT * FROM `room_facilities` WHERE `room_id`=?", [$frm_data['get_room']], 'i');

    $room_data = mysqli_fetch_assoc($res1);
    $facilities = [];
    $features = [];

    if (mysqli_num_rows($res2) > 0) {
        while ($row = mysqli_fetch_assoc($res2)) {
            array_push($features, $row['features_id']);
        }
    }

    if (mysqli_num_rows($res3) > 0) {
        while ($row = mysqli_fetch_assoc($res3)) {
            array_push($facilities, $row['facilities_id']);
        }
    }

    $data = ["roomdata" => $room_data, "features" => $features, "facilities" => $facilities];

    $data = json_encode($data);

    echo $data;
}

if (isset($_POST['edit_room'])) {
    $features = filteration(json_decode($_POST['features']));
    $facilities = filteration(json_decode($_POST['facilities']));
    $frm_data = filteration($_POST);

    $q1 = "UPDATE `rooms` SET `name`=?,`area`=?,`price`=?,`quantity`=?,`adult`=?,`children`=?,
    `description`=? WHERE `id`=?";
    $values = [$frm_data['name'], $frm_data['area'], $frm_data['price'], $frm_data['quantity'], $frm_data['adult'], $frm_data['children'], $frm_data['desc'], $frm_data['room_id']];

    if (update($q1, $values, 'siiiiisi')) {
        $flag = 1;
    }

    $del_features = delete("DELETE FROM `room_features` WHERE `room_id` = ?", [$frm_data['room_id']], 'i');
    $del_facilities = delete("DELETE FROM `room_facilities` WHERE `room_id` = ?", [$frm_data['room_id']], 'i');

    if (!($del_facilities && $del_features)) {
        $flag = 0;
    }
    $roomid = mysqli_insert_id($con);

    $q2 = "INSERT INTO `room_facilities`(`room_id`, `facilities_id`) VALUES (?,?)";

    if ($stmt = mysqli_prepare($con, $q2)) {
        foreach ($facilities as $f) {
            mysqli_stmt_bind_param($stmt, 'ii', $frm_data['room_id'], $f);
            mysqli_execute($stmt);
        }
        $flag = 1;
        mysqli_stmt_close($stmt);
    } else {
        $flag = 1;
        die('querry cannot prepared - INSERT');
    }

    $q3 = "INSERT INTO `room_features`(`room_id`, `features_id`) VALUES (?,?)";

    if ($stmt = mysqli_prepare($con, $q3)) {
        foreach ($features as $fe) {
            mysqli_stmt_bind_param($stmt, 'ii', $frm_data['room_id'], $fe);
            mysqli_execute($stmt);
        }
        $flag = 1;
        mysqli_stmt_close($stmt);
    } else {
        $flag = 0;
        die('querry cannot prepared - INSERT');
    }


    if ($flag == 1) {
        echo 1;
    } else {
        echo 0;
    }
}

if (isset($_POST['toggle_status'])) {
    $frm_data = filteration($_POST);

    $q = "UPDATE `rooms` SET `status`=? WHERE `id`=?";
    $v = [$frm_data['value'], $frm_data['toggle_status']];
    if (update($q, $v, 'ii')) {
        echo 1;
    } else {
        echo 0;
    }
}

if (isset($_POST["add_image"])) {
    $frm_Data = filteration($_POST);
    $img_r = upload_img($_FILES['image'], ROOMS_FOLDER);
    if ($img_r == 'inv_img') {
        echo $img_r;
    } else if ($img_r == 'inv_size') {
        echo $img_r;
    } else if ($img_r == 'upd_failed') {
        echo $img_r;
    } else {
        $q = "INSERT INTO `room_images`(`room_id`, `image`) VALUES (?,?)";
        $values = [$frm_Data['room_id'], $img_r];
        $res = insert($q, $values, 'is');
        echo $res;
    }
}

if (isset($_POST["get_room_images"])) {
    $frm_Data = filteration($_POST);
    $res = select("SELECT * FROM `room_images` WHERE `room_id` = ?", [$frm_Data['get_room_images']], 'i');

    $path = ROOMS_IMG_PATH;

    while ($row = mysqli_fetch_assoc($res)) {
        if ($row['thumb']) {
            $thumb_btn = "<i class='bi bi-check-lg text-light bg-success rounded fs-5 px-2 py-1'></i>";
        } else {
            $thumb_btn = "<button onclick='thumb_image($row[srno], $row[room_id])' class ='btn btn-lg btn-secondary shadow-none'><i class='bi bi-check-lg text-light rounded fs-5 px-2 py-1'></i></button>";
        }
        echo <<<ata
        <tr class="align-middle">
        <td><img src="$path$row[image]" class="img-fluid"></td>
        <td>$thumb_btn</td>
        <td><button onclick='rem_image($row[srno], $row[room_id])' class ='btn btn-lg btn-danger shadow-none'><i class="bi bi-trash"></i></button></td>
        </tr>
        ata;
    }
}

if (isset($_POST['rem_image'])) {
    $frm_data = filteration($_POST);
    $values = [$frm_data['image_id'], $frm_data['room_id']];

    $pre_q = "SELECT * FROM `room_images` WHERE `srno` = ? AND `room_id`=?";
    $res = select($pre_q, $values, 'ii');
    $img = mysqli_fetch_assoc($res);

    if (delete_img($img['image'], ROOMS_FOLDER)) {
        $q = "DELETE FROM `room_images` WHERE `srno` = ? AND `room_id` = ?";
        $res = delete($q, $values, 'ii');
        echo $res;
    } else {
        echo 0;
    }
}

if (isset($_POST['thumb_image'])) {
    $frm_data = filteration($_POST);

    $pre_q = "UPDATE `room_images` SET `thumb`=? WHERE `room_id`=?";
    $pre_v = [0, $frm_data['room_id']];
    $pre_res = update($pre_q, $pre_v, 'ii');

    $q = "UPDATE `room_images` SET `thumb`=? WHERE `srno`=? AND`room_id`=?";
    $v = [1, $frm_data['image_id'], $frm_data['room_id']];
    $res = update($q, $v, 'iii');
    echo $res;
}

if (isset($_POST['remove_room'])) {
    $frm_data = filteration($_POST);

    $res1 = select("SELECT * FROM `room_images` WHERE `room_id` = ?", [$frm_data['room_id']], 'i');
    while ($row = mysqli_fetch_assoc($res1)) {
        delete_img($row['image'], ROOMS_FOLDER);
    }

    $res2 = delete("DELETE FROM `room_images` WHERE `room_id`=?", [$frm_data['room_id']], 'i');
    $res3 = delete("DELETE FROM `room_features` WHERE `room_id`=?", [$frm_data['room_id']], 'i');
    $res4 = delete("DELETE FROM `room_facilities` WHERE `room_id`=?", [$frm_data['room_id']], 'i');
    $res5 = update("UPDATE `rooms` SET `removed`=? WHERE `id`=?", [1, $frm_data['room_id']], 'ii');

    if ($res4 || $res3 || $res2 || $res5) {
        echo 1;
    } else {
        echo 0;
    }
}
?>