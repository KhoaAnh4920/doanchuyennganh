<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Session;
use Redirect;
use DB;
session_start();
use Cart;
use View;
use Carbon\Carbon;

class CartController extends Controller
{
    public function cart(){
        // Header //
        $cate_of_Apple = DB::table("danhmucsanpham")
            ->whereRaw('danhmucsanpham.maDanhMuc IN (select dbsanpham.maDanhMuc FROM dbsanpham JOIN thuonghieu on thuonghieu.maThuongHieu = dbsanpham.maThuongHieu WHERE thuonghieu.maThuongHieu = 1)')
            ->get();
        $cate_of_Gear = DB::table("danhmucsanpham")
            ->select('tenDanhMuc', 'slug')
            ->where('danhMucCha', 14)
            ->get();

        $contentCart = Cart::content();
      
        //Cart::destroy();

        View::share('cate_of_Apple', $cate_of_Apple);
        View::share('cate_of_Gear', $cate_of_Gear);
        
        return view('frontend.pages.orderPages.cart')
        ->with('contentCart', $contentCart);
    }

    public function addCart(Request $request){
        $data = array();
        $pro_id = $request->product_id;
        $qty_pro = $request->qty_pro;
  
        $product = DB::table("dbsanpham")
        ->join("danhmucsanpham", function($join){
            $join->on("dbsanpham.maDanhMuc", "=", "danhmucsanpham.maDanhMuc");
        })
        ->join("thuonghieu", function($join){
            $join->on("thuonghieu.maThuongHieu", "dbsanpham.maThuongHieu", "=");
        })
        ->select("dbsanpham.*", "danhmucsanpham.tenDanhMuc", "danhmucsanpham.slug as slug_Cate","thuonghieu.tenThuongHieu", "thuonghieu.slug as slug_Brand")
        ->where("dbsanpham.masanpham", "=", $pro_id)
        ->first();
        
        $data['id']= $product->maSanPham;
        $data['qty']= $qty_pro;
        $data['price']= $product->giaSanPham;
        $data['name']= $product->tenSanPham;
        $data['options']['image']= $product->hinhAnh;
        $data['options']['category']= $product->tenDanhMuc;
        $data['options']['brand']= $product->tenThuongHieu;
        $data['options']['slug_Pro']= $product->slug;
        $data['options']['slug_Cate']= $product->slug_Cate;
        $data['options']['slug_Brand']= $product->slug_Brand;


        Cart::add($data);

        return redirect()->back()->with('error_code', 5);
    }
    public function DeleteItemCart($rowId){

        Cart::remove($rowId);
        return redirect()->back();
    }
    public function updateCart(Request $request){

        $data = array();
        $data = $request->quanlity;
        //var_dump($data); exit;

       foreach($data as $key => $qty_pro){
           $rowId = $key;
           $qty = $qty_pro;
           Cart::update($rowId, $qty);
       }
        return redirect()->back()->with('error_code', 8)->send();;
    }
    public function DeleteAllCart(){
        if(Cart::count() != 0){
            Cart::destroy();
        }
        return redirect()->back();
    }

    public function checkLogin(){
        $user_id = Session::get('user_id');
        if($user_id == null){
            return Redirect::to('/login.html')->with('error_code', 7)->send();
        }
            //return redirect()->back()->with('error_code', 7)->send();
    }

    public function order(){
        // Check login //
        $this->checkLogin();

        // Header //
        $cate_of_Apple = DB::table("danhmucsanpham")
            ->whereRaw('danhmucsanpham.maDanhMuc IN (select dbsanpham.maDanhMuc FROM dbsanpham JOIN thuonghieu on thuonghieu.maThuongHieu = dbsanpham.maThuongHieu WHERE thuonghieu.maThuongHieu = 1)')
            ->get();
        $cate_of_Gear = DB::table("danhmucsanpham")
            ->select('tenDanhMuc', 'slug')
            ->where('danhMucCha', 14)
            ->get();
        //var_dump($cate_of_Gear); exit;

        // end header

        $users_id = Session::get('user_id');
        $info_user = DB::table('users')->where('users_id', $users_id)->get();
        
        $cart_content = Cart::content();

        View::share('cate_of_Apple', $cate_of_Apple);
        View::share('cate_of_Gear', $cate_of_Gear);

        return view('frontend.pages.orderPages.order')->with('cart_content',$cart_content)->with('info_user', $info_user);
    }
    public function handleOrder(Request $request){
        $data = array();

        // Thêm vào data đơn hàng //
        $data['tenNguoiNhanHang'] = $request->order_cusName;
        $data['soDienThoai'] = $request->order_cusPhone;
        //$data['ngayDatHang'] = $request->order_cusPhone;
        $data['diaChiGiaoHang'] = $request->order_cusAddress;
        $data['tongTien'] = $request->total_price;
        $data['trangThaiDonHang'] = 0;
        $data['users_id'] = $request->users_id;
        $data['ngayDatHang'] = Carbon::now('Asia/Ho_Chi_Minh');

        DB::table('donhang')->insert($data);

        // Thêm vào data chi tiết đơn hàng //

        $get_id_order = DB::table('donhang')->select('maDonHang')->orderBy('maDonHang', 'DESC')->first();

        $id_order = $get_id_order->maDonHang;
   

        $cart_content = Cart::content();
        $dataChiTiet = array();
        $dataChiTiet['maDonHang'] = $id_order;
        foreach($cart_content as $key =>$cart_pro){
            $dataChiTiet['maSanPham'] = $cart_pro->id;
            $dataChiTiet['giaSanPham'] = $cart_pro->price;
            $dataChiTiet['soLuong'] = $cart_pro->qty;
            //var_dump($dataChiTiet); exit;
            DB::table('chitietdonhang')->insert($dataChiTiet);
        }
        Cart::destroy();
        return Redirect::to('/trang-chu.html')->with('error_code', 6);
    }
}