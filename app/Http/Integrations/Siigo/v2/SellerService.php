<?php

namespace App\Http\Integrations\Siigo\v2;

use App\Http\Integrations\Siigo\v2\Adapters\SellerAdapter;
use App\Http\Integrations\Siigo\v2\TokenService;
use App\Models\Siigo\Seller;
use Carbon\Carbon;
use Exception;

class SellerService
{
    private $sellerAdapter;

    public function __construct(SellerAdapter $sellerAdapter, TokenService $tokenService)
    {
        $this->sellerAdapter = $sellerAdapter;
        $this->tokenService = $tokenService;
    }

    public function getAll()
    {
        // $sellers = Seller::all();
        //  // Si la tabla esta vacia o alguna esta vencida se actualizan los campos.
        //  if($sellers->count() === 0)
        //  {
        //      $this->upsert();
        //  }

        //  foreach($sellers as $seller)
        //  {
        //      if($seller->is_expired)
        //      {
        //         $this->upsert();
        //      }
        //      continue;
        //  }

         return Seller::all();
    }

    public function upsert()
    {
        try{
            $token = $this->tokenService->get();
            $sellers = $this->sellerAdapter->getAll($token['token']);
            //dd($sellers->json()['results']);
            foreach ($sellers->json()['results'] as $seller) {

                $this->save($seller);
            }
            return Seller::all();
        }
        catch(Exception $exeption)
        {
           dd($exeption);
        }
    }

    public function save($seller)
    {
        try{
            $sellerModel = Seller::updateOrCreate(
                ['identification' => $seller["identification"]],
                $seller
            );

        }catch(Exception $exeption)
        {
            dd($exeption);
        }

        return $sellerModel;
    }

    public function searchByUserName($userName = null)
    {
        $userName = isset($userName) ? $userName : config('Siigo.siigo_user_name_v2');
        $seller = Seller::where('username',$userName)->first();
        if(!isset($seller))
        {
            $this->upsert();
        }
        return Seller::where('username',$userName)->first();
    }
}
