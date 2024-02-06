<?php
namespace App\Controllers\Simlab;

use App\Controllers\BaseController;
use App\Models\Bot\ChatbotUserMahasiswaModel;
use App\Models\Bot\ChatbotUserStaffModel;
use App\Models\Master\MahasiswaModel;
use App\Models\Master\StafModel;
use App\Models\Simlab\AlatLaboratoriumModel;
use App\Models\Simlab\DetailPeminjamanAlatModel;
use App\Models\Simlab\JadwalRuangModel;
use App\Models\Simlab\PeminjamanAlatModel;
use App\Models\Simlab\PeminjamanRuangModel;
use App\Models\Simlab\RuangLaboratoriumModel;
use CodeIgniter\Config\Services;
use Dompdf\Dompdf;
use Myth\Auth\Models\GroupModel;
use Ramsey\Uuid\Uuid;

class PeminjamanController extends BaseController
{
    public function __construct()
    {
        $this->alatlab = new AlatLaboratoriumModel();
        $this->ruanglab = new RuangLaboratoriumModel();
        $this->mahasiswa = new MahasiswaModel();
        $this->staf = new StafModel();
        $this->UserMahasiswa = new ChatbotUserMahasiswaModel();
        $this->UserStaff = new ChatbotUserStaffModel();
        $this->group = new GroupModel();
        $this->pinjamalat = new PeminjamanAlatModel();
        $this->detailpinjamalat = new DetailPeminjamanAlatModel();
        $this->pinjamruang = new PeminjamanRuangModel();
        $this->jadwal = new JadwalRuangModel();
        $this->validation = \Config\Services::validation();
        $this->session = session();
    }

    public function index()
    {
        $data = [
            'title' => 'Data Peminjaman',
            'activePage' => 'peminjaman',
        ];
        // dd($data);
        return view('simlab/peminjaman/index', $data);
    }

    public function pengajuan_peminjaman_alat()
    {
        if ($this->group->inGroup('mahasiswa', $this->auth->user()->id)) {

            $data = [
                'title' => 'Pengajuan Peminjaman Alat Laboratorium',
                'alatlab' => $this->alatlab->getAlatLabBaikStok(),
                'mahasiswa' => $this->mahasiswa->getMahasiswabyUserId(),
                'validation' => $this->validation,
                'activePage' => 'pengajuan-peminjaman/alat-laboratorium',
            ];
            return view('simlab/peminjaman/pengajuan_peminjaman_alat', $data);
        } else {
            $data = [
                'title' => 'Pengajuan Peminjaman Alat Laboratorium',
                'alatlab' => $this->alatlab->getAlatLabBaikStok(),
                'staf' => $this->staf->getStafbyUserId(),
                'validation' => $this->validation,
                'activePage' => 'pengajuan-peminjaman/alat-laboratorium',
            ];
            return view('simlab/peminjaman/pengajuan_peminjaman_alat', $data);
        }
    }
    public function pengajuan_peminjaman_alat_simpan()
    {
        $uuid = Uuid::uuid4();
        $id_pinjam_alat = $uuid->toString();
        $id_mahasiswa = $this->request->getVar('id_mahasiswa');
        $id_staff = $this->request->getVar('id_staff');
        $keperluan = $this->request->getVar('keperluan');
        $tanggal_ajuan = round(microtime(true) * 1000);
        $tanggal_pinjam = $this->request->getVar('tanggal_pinjam');
        $tglkembali = $this->request->getVar('tanggal_kembali');
        $makspinjam = strtotime($tanggal_pinjam) + (7 * 24 * 60 * 60);
        if (strtotime($tglkembali) > $makspinjam) {
            return redirect()->back()->with('error', 'Maksimal waktu peminjaman adalah 7 hari!');
        }
        $tanggal_kembali = round(strtotime($tglkembali) * 1000);

        $rules = [
            'id_alat' => [
                'label' => "Nama alat laboratorium",
                'rules' => "required",
                'errors' => [
                    'required' => "{field} harus diisi",
                ],
            ],
            'keperluan' => [
                'label' => "Keperluan peminjaman",
                'rules' => "required",
                'errors' => [
                    'required' => "{field} harus diisi",
                ],
            ],
            'tanggal_pinjam' => [
                'label' => "Tanggal peminjaman",
                'rules' => "required",
                'errors' => [
                    'required' => "{field} harus diisi",
                ],
            ],
            'tanggal_kembali' => [
                'label' => "Tanggal pengembalian",
                'rules' => "required",
                'errors' => [
                    'required' => "{field} harus diisi",
                ],
            ],
        ];

        if ($this->validate($rules)) {
            // Membuat array untuk menyimpan pesan kesalahan ketersediaan alat
            $AlatTidakTersedia = [];

            // Mengambil data alat yang diminta
            $id_alat = $this->request->getVar('id_alat');
            $jumlah_pinjam = $this->request->getVar('jumlah_pinjam');

            // Memeriksa ketersediaan alat
            for ($i = 0; $i < count($id_alat); $i++) {
                $alatlab = $this->alatlab->find($id_alat[$i]);
                if ($alatlab) {
                    if ($alatlab->stok >= $jumlah_pinjam[$i]) {
                        // Alat tersedia, lanjutkan
                        $dataPinjam = [
                            'id_pinjam_alat' => $id_pinjam_alat,
                            'id_alat' => $id_alat[$i],
                            'jumlah_pinjam' => $jumlah_pinjam[$i],
                        ];
                        $dataDetailPinjam[] = $dataPinjam;

                    } else {
                        // Alat tidak cukup tersedia
                        $AlatTidakTersedia[] = $alatlab->nama_alat;
                    }
                }
            }

            if (empty($AlatTidakTersedia)) {
                // Semua alat tersedia, lakukan insert data
                $data = [
                    'id_pinjam_alat' => $id_pinjam_alat,
                    'id_mahasiswa' => $id_mahasiswa,
                    'id_staff' => $id_staff,
                    'keperluan' => $keperluan,
                    'tanggal_ajuan' => $tanggal_ajuan,
                    'tanggal_pinjam' => $tanggal_pinjam,
                    'tanggal_kembali' => $tanggal_kembali,
                ];
                $this->pinjamalat->insert($data);
                $this->detailpinjamalat->insertBatch($dataDetailPinjam);
            } else {
                // Ada alat yang tidak tersedia, berikan pesan kesalahan
                return redirect()->back()->with('error', 'Alat tidak tersedia : ' . implode(', ', $AlatTidakTersedia));
            }
            // $data = [
            //     'id_pinjam_alat' => $uuid,
            //     'id_mahasiswa' => $id_mahasiswa,
            //     'id_staff' => $id_staff,
            //     'keperluan' => $keperluan,
            //     'tanggal_ajuan' => $tanggal_ajuan,
            //     'tanggal_pinjam' => $tanggal_pinjam,
            //     'tanggal_kembali' => $tanggal_kembali,
            // ];

            // $this->pinjamalat->insert($data);

            // $dataDetailPinjam = [];
            // $uuid1 = Uuid::uuid4();
            // $id_detail_pinjam_alat = $uuid1->toString();
            // $id_alat = $this->request->getVar('id_alat');
            // $jumlah_pinjam = $this->request->getVar('jumlah_pinjam');
            // $jumlah_alat = count((array) $this->request->getVar('id_alat'));

            // for ($i = 0; $i < $jumlah_alat; $i++) {
            //     $dataPinjam = array(
            //         'id_detail_pinjam_alat' => $id_detail_pinjam_alat++,
            //         'id_pinjam_alat' => $id_pinjam_alat,
            //         'id_alat' => $id_alat[$i],
            //         'jumlah_pinjam' => $jumlah_pinjam[$i],
            //     );
            //     $dataDetailPinjam[] = $dataPinjam;
            // }

            // $this->detailpinjamalat->insertBatch($dataDetailPinjam);

            session()->setFlashdata('success', 'Berhasil Mengirim Pengajuan Peminjaman Alat Laboratorium!');

            // Mengirim notifikasi kepada laboran
            $url1 = 'http://api2.myfin.id:4000/bot/api/publish';
            $laboranRecipient = $this->staf->getLaboran(); // Mendapatkan daftar laboran yang akan menerima notifikasi

            foreach ($laboranRecipient as $laboran) {
                $recipient = $laboran->id_staf;
                $pesan = 'Notifikasi : '
                    . 'Terdapat ajuan peminjaman alat laboratorium. '
                    . 'Mohon untuk melakukan konfirmasi permintaan peminjaman tersebut. '
                    . 'Terima kasih';
                $pesan_email = $this->request->getVar('pesan_email') ?? '';
                $subject_email = $this->request->getVar('email_subject') ?? '';
                $platform = ['telegram']; // Platform notifikasi yang ingin digunakan
                $platformdata = array(
                    'whatsapp' => false,
                    'telegram' => in_array('telegram', $platform),
                    'email' => in_array('email', $platform),
                );
                $datapesan = array(
                    'receiver' => $recipient,
                    'message' => $pesan,
                    'email_subject' => ($subject_email !== '') ? $subject_email : 'default',
                    'email_message' => ($pesan_email !== '') ? $pesan_email : 'default',
                    'platform' => $platformdata,
                );

                $jsonData = json_encode($datapesan);

                $request = Services::curlrequest();
                $response = $request->setBody($jsonData)
                    ->setHeader('Content-Type', 'application/json')
                    ->setHeader('x-api-key', '12345678')
                    ->setHeader('App-auth', 'simlabd3tipsdku001-1')
                    ->post($url1);
            }

            $url2 = 'http://api2.myfin.id:4000/bot/api/publishdelay';
            $peminjam = $this->request->getVar('id_mahasiswa') ?? $this->request->getVar('id_staff');
            $recipient = $peminjam;
            $pesan = 'Mengingatkan, batas waktu peminjaman alat laboratorium adalah besok. Harap dikembalikan tepat waktu. Terima kasih.';
            $pesan_email = $this->request->getVar('pesan_email') ?? '';
            $subject_email = $this->request->getVar('email_subject') ?? '';
            $platform = ['telegram'];

            $platformdata = array(
                'whatsapp' => false,
                'telegram' => in_array('telegram', $platform),
                'email' => in_array('email', $platform),
            );

            $tanggal = date('m/d/Y', strtotime('-1 day', $tanggal_kembali / 1000));
            $jam = '13';
            $menit = '30';

            $parts = explode('/', $tanggal);
            $bulan = intval($parts[0]);
            $tanggal = intval($parts[1]);
            $tahun = intval($parts[2]);

            $timedata = array(
                'year' => $tahun,
                'month' => $bulan,
                'day' => $tanggal,
                'hour' => $jam,
                'minute' => $menit,
            );

            $datapesan = array(
                'receiver' => $recipient,
                'message' => $pesan,
                'email_subject' => ($subject_email !== '') ? $subject_email : 'default',
                'pesan_email' => ($pesan_email !== '') ? $pesan_email : 'default',
                'platform' => $platformdata,
                'time' => $timedata,
            );

            $jsonData = json_encode($datapesan);

            $request = Services::curlrequest();
            $response = $request->setBody($jsonData)
                ->setHeader('Content-Type', 'application/json')
                ->setHeader('x-api-key', '12345678')
                ->setHeader('App-auth', 'simlabd3tipsdku001-1')
                ->post($url2);

            return redirect()->to('simlab/peminjaman/data-pengajuan-peminjaman/alat-laboratorium')->with('status_icon', 'success')->with('status_text', 'Data Berhasil ditambah');
        } else {
            if ($this->group->inGroup('mahasiswa', $this->auth->user()->id)) {
                $data = [
                    'title' => 'Pengajuan Peminjaman Alat Laboratorium',
                    'alatlab' => $this->alatlab->getAlatLabBaikStok(),
                    'mahasiswa' => $this->mahasiswa->getMahasiswabyUserId(),
                    'validation' => $this->validation,
                    'activePage' => 'pengajuan-peminjaman/alat-laboratorium',
                ];
                return view('simlab/peminjaman/pengajuan_peminjaman_alat', $data);
            } else {
                $data = [
                    'title' => 'Pengajuan Peminjaman Alat Laboratorium',
                    'alatlab' => $this->alatlab->getAlatLabBaikStok(),
                    'staf' => $this->staf->getStafbyUserId(),
                    'validation' => $this->validation,
                    'activePage' => 'pengajuan-peminjaman/alat-laboratorium',
                ];
                return view('simlab/peminjaman/pengajuan_peminjaman_alat', $data);
            }
        }
    }

    public function pengajuan_peminjaman_ruang()
    {
        if ($this->group->inGroup('mahasiswa', $this->auth->user()->id)) {

            $data = [
                'title' => 'Pengajuan Peminjaman Ruang Laboratorium',
                'ruanglab' => $this->ruanglab->findAll(),
                'mahasiswa' => $this->mahasiswa->getMahasiswabyUserId(),
                'validation' => $this->validation,
                'activePage' => 'pengajuan-peminjaman/ruang-laboratorium',
            ];
            return view('simlab/peminjaman/pengajuan_peminjaman_ruang', $data);
        } else {
            $data = [
                'title' => 'Pengajuan Peminjaman Ruang Laboratorium',
                'ruanglab' => $this->ruanglab->findAll(),
                'staf' => $this->staf->getStafbyUserId(),
                'validation' => $this->validation,
                'activePage' => 'pengajuan-peminjaman/ruang-laboratorium',
            ];
            return view('simlab/peminjaman/pengajuan_peminjaman_ruang', $data);
        }
    }

    public function pengajuan_peminjaman_ruang_simpan()
    {
        $uuid = Uuid::uuid4();
        $id_pinjam_ruang = $uuid->toString();
        $id_ruang = $this->request->getVar('id_ruang');
        $id_mahasiswa = $this->request->getVar('id_mahasiswa');
        $id_staff = $this->request->getVar('id_staff');
        $keperluan = $this->request->getVar('keperluan');
        $tanggal_ajuan = round(microtime(true) * 1000);
        $hari = $this->request->getVar('hari');
        $tanggal_pinjam = $this->request->getVar('tanggal_pinjam');
        $waktu_mulai = $this->request->getVar('waktu_mulai');
        $waktu_selesai = $this->request->getVar('waktu_selesai');
        $status_peminjaman = '';
        $tahun_ajaran = date('Y', strtotime($tanggal_pinjam)); // Mendapatkan tahun dari tanggal pinjam
        $rules = [
            'id_ruang' => [
                'label' => "Ruang laboratorium",
                'rules' => "required",
                'errors' => [
                    'required' => "{field} harus diisi",
                ],
            ],
            'keperluan' => [
                'label' => "Keperluan peminjaman",
                'rules' => "required",
                'errors' => [
                    'required' => "{field} harus diisi",
                ],
            ],
            'hari' => [
                'label' => "Hari peminjaman",
                'rules' => "required",
                'errors' => [
                    'required' => "{field} harus diisi",
                ],
            ],
            'tanggal_pinjam' => [
                'label' => "Tanggal peminjaman",
                'rules' => "required",
                'errors' => [
                    'required' => "{field} harus diisi",
                ],
            ],
            'waktu_mulai' => [
                'label' => "Waktu mulai peminjaman",
                'rules' => "required",
                'errors' => [
                    'required' => "{field} harus diisi",
                ],
            ],
            'waktu_selesai' => [
                'label' => "Waktu selesai peminjaman",
                'rules' => "required",
                'errors' => [
                    'required' => "{field} harus diisi",
                ],
            ],
        ];

        $cekKetersediaanRuangByJadwalPraktikum = $this->jadwal->cekKetersediaanRuangByJadwalPraktikum($id_ruang, $hari, $tahun_ajaran, $waktu_mulai, $waktu_selesai);
        if ($cekKetersediaanRuangByJadwalPraktikum > 0) {
            session()->setFlashdata('error', 'Ruang laboratorium sudah digunakan pada hari dan waktu tersebut!');
            return redirect()->to('simlab/pengajuan-peminjaman/ruang-laboratorium/')->with('status_icon', 'error');
        }
 
        $cekKetersediaanRuangByPeminjaman = $this->pinjamruang->cekKetersediaanRuangByPeminjaman($id_ruang, $hari, $tanggal_pinjam, $waktu_mulai, $waktu_selesai, $status_peminjaman = 'Sedang Digunakan');
        if ($cekKetersediaanRuangByPeminjaman > 0) {
            session()->setFlashdata('error', 'Ruang laboratorium sudah digunakan pada hari dan waktu tersebut!');
            return redirect()->to('simlab/pengajuan-peminjaman/ruang-laboratorium/')->with('status_icon', 'error');
        }


        if ($this->validate($rules)) {
            $data = [
                'id_pinjam_ruang' => $uuid,
                'id_ruang' => $id_ruang,
                'id_mahasiswa' => $id_mahasiswa,
                'id_staff' => $id_staff,
                'keperluan' => $keperluan,
                'tanggal_ajuan' => $tanggal_ajuan,
                'hari' => $hari,
                'tanggal_pinjam' => $tanggal_pinjam,
                'waktu_mulai' => $waktu_mulai,
                'waktu_selesai' => $waktu_selesai,
            ];

            $this->pinjamruang->insert($data);
            session()->setFlashdata('success', 'Berhasil Mengirim Pengajuan Peminjaman Ruang Laboratorium!');

            $url = 'http://api2.myfin.id:4000/bot/api/publish';

            $laboranRecipient = $this->staf->getLaboran();
            foreach ($laboranRecipient as $laboran) {
                $recipient = $laboran->id_staf;
                $pesan = 'Notifikasi : '
                    . 'Terdapat ajuan peminjaman ruang laboratorium. '
                    . 'Mohon untuk melakukan konfirmasi permintaan peminjaman tersebut. '
                    . 'Terima kasih';
                $pesan_email = $this->request->getVar('pesan_email') ?? '';
                $subject_email = $this->request->getVar('email_subject') ?? '';
                $platform = ['telegram'];
                $platformdata = array(
                    'whatsapp' => false,
                    'telegram' => in_array('telegram', $platform),
                    'email' => in_array('email', $platform),
                );

                $datapesan = array(
                    'receiver' => $recipient,
                    'message' => $pesan,
                    'email_subject' => ($subject_email !== '') ? $subject_email : 'default',
                    'email_message' => ($pesan_email !== '') ? $pesan_email : 'default',
                    'platform' => $platformdata,
                );

                $jsonData = json_encode($datapesan);

                $request = Services::curlrequest();
                $response = $request->setBody($jsonData)
                    ->setHeader('Content-Type', 'application/json')
                    ->setHeader('x-api-key', '12345678')
                    ->setHeader('App-auth', 'simlabd3tipsdku001-1')
                    ->post($url);
            }
            return redirect()->to('simlab/peminjaman/data-pengajuan-peminjaman/ruang-laboratorium')->with('status_icon', 'success')->with('status_text', 'Data Berhasil ditambah');
        } else {
            if ($this->group->inGroup('mahasiswa', $this->auth->user()->id)) {

                $data = [
                    'title' => 'Pengajuan Peminjaman Ruang Laboratorium',
                    'ruanglab' => $this->ruanglab->findAll(),
                    'mahasiswa' => $this->mahasiswa->getMahasiswabyUserId(),
                    'validation' => $this->validation,
                    'activePage' => 'pengajuan-peminjaman/ruang-laboratorium',
                ];
                return view('simlab/peminjaman/pengajuan_peminjaman_ruang', $data);
            } else {
                $data = [
                    'title' => 'Pengajuan Peminjaman Ruang Laboratorium',
                    'ruanglab' => $this->ruanglab->findAll(),
                    'staf' => $this->staf->getStafbyUserId(),
                    'validation' => $this->validation,
                    'activePage' => 'pengajuan-peminjaman/ruang-laboratorium',
                ];
                return view('simlab/peminjaman/pengajuan_peminjaman_ruang', $data);
            }
        }
    }
    
    public function data_peminjaman_alat()
    {
        $data = [
            'title' => 'Data Peminjaman Alat Laboratorium',
            'peminjamanalat' => $this->pinjamalat->getAlatDipinjamById(),
            'activePage' => 'peminjaman/data-peminjaman/alat-laboratorium',
        ];
        return view('simlab/peminjaman/data_peminjaman_alat', $data);

    }
    public function data_peminjaman_ruang()
    {
        $data = [
            'title' => 'Data Peminjaman Ruang Laboratorium',
            'peminjamanruang' => $this->pinjamruang->getRuangDipinjamById(),
            'activePage' => 'peminjaman/data-peminjaman/ruang-laboratorium',
        ];
        return view('simlab/peminjaman/data_peminjaman_ruang', $data);

    }
    public function data_pengajuan_alat()
    {
        $data = [
            'title' => 'Data Pengajuan Peminjaman Alat Laboratorium',
            'pengajuanalat' => $this->pinjamalat->getAlatDipinjamById(),
            'activePage' => 'peminjaman/data-pengajuan/alat-laboratorium',
        ];
        return view('simlab/peminjaman/data_pengajuan_alat', $data);

    }
    public function data_pengajuan_ruang()
    {
        $data = [
            'title' => 'Data Pengajuan Peminjaman Ruang Laboratorium',
            'pengajuanruang' => $this->pinjamruang->getRuangDipinjamById(),
            'activePage' => 'peminjaman/data-pengajuan/ruang-laboratorium',
        ];
        return view('simlab/peminjaman/data_pengajuan_ruang', $data);

    }
    public function detail_peminjaman_alat($id)
    {
        $pinjamalat = $this->pinjamalat->getAlatDipinjamForDetail($id);
        $detailpinjamalat = $this->detailpinjamalat->getDetailAlatDipinjam($id);
        $data = array(
            'pinjamalat' => $pinjamalat,
            'detailpinjamalat' => $detailpinjamalat,
        );
        // dd($data);
        return json_encode($data);
    }

    public function detail_peminjaman_ruang($id = null)
    {
        $data = $this->pinjamruang->getRuangDipinjamAll($id);
        return json_encode($data);
    }
    public function riwayat_peminjaman_alat()
    {
        $data = [
            'title' => 'Riwayat Peminjaman Alat Laboratorium',
            'peminjamanalat' => $this->pinjamalat->getAlatDipinjamById(),
            'activePage' => 'peminjaman/riwayat-peminjaman/alat-laboratorium',
        ];
        return view('simlab/peminjaman/riwayat_peminjaman_alat', $data);
    }
    public function riwayat_peminjaman_ruang()
    {
        $data = [
            'title' => 'Riwayat Peminjaman Ruang Laboratorium',
            'peminjamanruang' => $this->pinjamruang->getRuangDipinjamById(),
            'activePage' => 'peminjaman/riwayat-peminjaman/ruang-laboratorium',
        ];
        return view('simlab/peminjaman/riwayat_peminjaman_ruang', $data);
    }

    public function generate_surat_pinjam_alat($id)
    {
        $peminjaman = $this->pinjamalat->getAlatDipinjamForDetail($id);
        $detail_peminjaman_alat = $this->detailpinjamalat->getDetailAlatDipinjam($id);
        $html = view('simlab/peminjaman/surat_peminjaman_alat', [
            'peminjaman' => $peminjaman,
            'detail_peminjaman_alat' => $detail_peminjaman_alat,
        ]);
        $pdf = new Dompdf();
        $pdf->loadHtml($html);
        $pdf->setPaper('A4', 'portrait');
        $pdf->render();
        $pdf->stream(date('Ymd') . '-Surat Peminjaman Alat Laboratorium.pdf');
    }
    
    public function generate_surat_pinjam_ruang($id)
    {
        $pinjamruang = $this->pinjamruang->getRuangDipinjamAll($id);
        $html = view('simlab/peminjaman/surat_peminjaman_ruang', [
            'pinjamruang' => $pinjamruang,
        ]);
        $pdf = new Dompdf(array('enable_remote' => true));
        $pdf->loadHtml($html);
        $pdf->setPaper('A4', 'portrait');
        $pdf->render();
        $pdf->stream(date('Ymd') . '-Surat Peminjaman Ruang Laboratorium.pdf');
    }
}