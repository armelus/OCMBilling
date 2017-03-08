<?php
/**
 * Created by PhpStorm.
 * User: Armel JONDO
 * Email : mc.armelus@gmail.com
 * Tel : +237-655-40-99-91
 * Date: 23/11/2016
 * Time: 06:16
 */

namespace Orange;

/**
 * Class OBilling
 * @package Orange
 */
class OBilling
{
    /**
     * Vous devez remplacer le contenu de cette constante par une url du genre :
	 * 		https://ocm-billing.Agrégateur.fr/OCM/v1/payment
	 *
	 * URL de billing en mode PRODUCTION
     */
    const PROD_URL = "Adresse de conexion à l'API de production";

    /**
	 * Vous devez remplacer le contenu de cette constante par une url du genre :
	 * 		https://ocm-billing.Agrégateur.fr/OCM/v1/payment
	 *
     * URL de biliing en mode DEVELOPEMENT
     */
    const DEV_URL = "Adresse de conexion à l'API de pré-production";

    /**
	 * Ceci est l'identifiant fourni par 
	 * votre Agrégateur pour facturer un abonné à votre service
	 * C'est un chiffre qu'il faudra renseigner
     * SERVICE ID de billing pour un abonne
     */
    const SERVICEID_ABONNE = 4;

    /**
	 * Ceci est l'identifiant fourni par 
	 * votre Agrégateur pour facturer un non abonné à votre service
	 * C'est un chiffre qu'il faudra renseigner
     * SERVICE ID de billing pour un non abonne
     */
    const SERVICEID_ACTE = 5;

    /**
     * @var array End User Informations
     */
    protected $enduser = array();

    /**
     * @var array Billind Informations
     */
    protected $billingInformation = array();

    /**
     * @var array Orange USSD User Identification
     */
    protected $userIdentification = array();

    /**
     * Categorie : IDENTIFICATION
     * Origine : Agrégateur
     * identifiant unique du client Ex : applecameroun
     *
     * @var string
     */
    protected $username;

    /**
     * Categorie : IDENTIFICATION
     * Origine : Agrégateur
     * mot de passe client
     * Ex : g6ORyKFvrCvKZpa
     *
     * @var string
     */
    protected $password;

    /**
     * Categorie : IDENTIFICATION
     * Origine : Agrégateur
     * Entier positif qui identifie de manière unique
     * le service pour lequel le client demande le billing.
     * Ex : 15478
	 *
     * @var string
     */
    protected $serviceID;

    /**
     * Categorie : TRANSACTION
     * Origine : CLIENT
     * Identifiant alphanumerique sur 64 caractères maximum.
     * maximum qui identifie de manière unique une demande de billing.
     * En cas de succès, la reutilisation de ce code entraînera une erreur.
     * Ex : ab.123456789
     * @var string
     */
    protected $uniqueTransactionID;

    /**
     * Categorie : TRANSACTION
     * Origine : CLIENT
     * Montant TTC qui doit être facturer au titulaire de la SIM Orange.
     * Nombre decimal (float) de la forme [0-9]+\.[0-9]{2}. Ex : 145.22
	 *
     * @var float
     */
    protected $amount;

    /**
     * Categorie : TRANSACTION
     * Origine : Agrégateur
     * Code ISO4217 de la devise correspondant au montant <amount>.
     * Associe au client lors de l’ouverture du compte. Ex : XAF
	 *
     * @var string
     */
    protected $currency;

    /**
     * Categorie : TRANSACTION
     * Origine : CLIENT
     * Code identifiant le <enduserid>.
     * “msisdn” ou “ise2"
	 *
     * @var string
     */
    protected $endUserType;

    /**
     * Categorie : TRANSACTION
     * Origine : CLIENT
     * Identification du titulaire de la SIM Orange que l’on souhaite biller. Peut être soit un
     * msisdn (+237012345678), soit un code ISE2 (fourni par Orange pour identifier le titulaire de la ligne)
	 *
     * @var string
     */
    protected $endUserID;

    /**
     * Categorie : TRANSACTION
     * Origine : CLIENT
     * Identification de type OrangeAPIToken, qui identifie le titulaire de la ligne et intègre son
     * autorisation d’être bille par l’editeur du service.
     *
	 * @var string
     */
    protected $enduserToken;

    /**
     * CAtegorie : TRANSACTION
     * Origine : CLIENT
     * Description commerciale de la transaction
     *
	 * @var string
     */
    protected $description;

    /**
     * When we are in PRODUCTION mode, set it on TRUE else set in on FALSE.
     * @var bool Implementation mode
     */
    protected $prod = false;

    /**
     * cURL option for whether to verify the peer's certificate or not.
     *
     * @var bool
     */
    protected $verifyPeerSSL = false;

    /**
     * Crete new OBilling Instance.
     * The user will retrieve Token with getTokenFromUrl()
     * OBilling constructor.
     * @param array $config
     */
    public function __construct($config = array())
    {
        if (array_key_exists('abonne', $config)) {
            if ($config['abonne'] == true) {
                $this->serviceID = self::SERVICEID_ABONNE;
            } else {
                $this->serviceID = self::SERVICEID_ACTE;
            }
        }

        if (array_key_exists('username', $config)) {
            $this->username = $config['username'];
        }

        if (array_key_exists('password', $config)) {
            $this->password = $config['password'];
        }

        if (array_key_exists('prod', $config)) {
            $this->prod = $config['prod'];
        } else {
            $this->prod = false;
        }

        if (array_key_exists('verifyPeerSSL', $config))
            $this->verifyPeerSSL = $config['verifyPeerSSL'];
        else
            $this->verifyPeerSSL = false;


        $datas = array();
        $datas['username'] = $this->username;
        $datas['password'] = $this->password;
        $datas['serviceid'] = $this->serviceID;

        $this->setUserIdentification($datas);

        $this->setUniqueTransactionID(md5(uniqid(mt_rand(0, 64), true)));
    }

    /**
     * @return string
     */
    public function getUsername()
    {
        return $this->username;
    }

    /**
     * @param string $username
     */
    public function setUsername($username)
    {
        $this->username = $username;
    }

    /**
     * @return string
     */
    public function getPassword()
    {
        return $this->password;
    }

    /**
     * @param string $password
     */
    public function setPassword($password)
    {
        $this->password = $password;
    }

    /**
     * @return string
     */
    public function getServiceID()
    {
        return $this->serviceID;
    }

    /**
     * @param string $serviceID
     */
    public function setServiceID($serviceID)
    {
        $this->serviceID = $serviceID;
    }

    /**
     * @return mixed
     */
    public function getEndUserType()
    {
        return $this->endUserType;
    }

    /**
     * @param mixed $endUserType
     */
    public function setEndUserType($endUserType)
    {
        $this->setEnduser(array('endusertype' => $endUserType));
        $this->endUserType = $endUserType;
    }

    /**
     * @return string
     */
    public function getEndUserID()
    {
        return $this->endUserID;
    }

    /**
     * @param string $endUserID
     */
    public function setEndUserID($endUserID)
    {
        $this->setEnduser(array('enduserid' => $endUserID));
        $this->endUserID = $endUserID;
    }

    /**
     * @return string
     */
    public function getEnduserToken()
    {
        return $this->enduserToken;
    }

    /**
     * @param string $enduserToken
     */
    public function setEnduserToken($enduserToken)
    {
        $this->setEnduser(array('endusertoken' => $enduserToken));
        $this->enduserToken = $enduserToken;
    }

    /**
     * Cette fonction retourne la reponse suite à une requête de billing.
     * Il faudra donc après avoir biller, analyser la reponse.
     * @return array|mixed
     */
    public function Bill()
    {
        $url = self::DEV_URL;

        if ($this->isProd())
            $url = self::PROD_URL;

        $datas = array();
        $userIdentification = $this->getUserIdentification();
        $billingInformation = $this->getBillingInformation();
        $endUser = $this->getEnduser();

        $billingInformation['enduser'] = $endUser;
        $billingRequest['useridentification'] = $userIdentification;
        $billingRequest['billinginformation'] = $billingInformation;
        $datas['billingrequest'] = $billingRequest;

        $headers = array('Content-Type: application/json');

        return $this->callAPI($headers, $datas, $url, 'POST', 201, true, 180);
    }

    /**
	 * Retourne l'état du mode facturation 
	 * Si $this->prod === TRUE alors 
	 *		vous êtes en mode Production
	 *	Sinon
	 * 		Vous êtes en mode Pré-production 
	 *
     * @return boolean
     */
    public function isProd()
    {
        return $this->prod;
    }

    /**
	 * Defini l'état du mode facturation
     * @param boolean $prod
     */
    public function setProd($prod)
    {
        $this->prod = $prod;
    }

    /**
	 *
     * @return array
     */
    public function getUserIdentification()
    {
        return $this->userIdentification;
    }

    /**
     * @param array $userIdentification
     */
    public function setUserIdentification($userIdentification)
    {
        $this->userIdentification = $userIdentification;
    }

    /**
     * @return array
     */
    public function getBillingInformation()
    {
        if (empty($this->billingInformation)) {
            $datas = array();
            $datas['uniquetransactionid'] = $this->getUniqueTransactionID();
            $datas['amount'] = $this->getAmount();
            $datas['currency'] = $this->getCurrency();
            $datas['enduser'] = $this->getEnduser();
            $datas['description'] = $this->getDescription();
            $this->billingInformation = $datas;
        }
        return $this->billingInformation;
    }

    /**
     * @param array $billingInformation
     */
    public function setBillingInformation($billingInformation)
    {
        if (!empty($billingInformation)) {
            if (array_key_exists('uniquetransactionid', $billingInformation)) {
                $this->billingInformation['uniquetransactionid'] = $billingInformation['uniquetransactionid'];
            }

            if (array_key_exists('amount', $billingInformation)) {
                $this->billingInformation['amount'] = $billingInformation['amount'];
            }

            if (array_key_exists('currency', $billingInformation)) {
                $this->billingInformation['currency'] = $billingInformation['currency'];
            }

            if (array_key_exists('enduser', $billingInformation)) {
                $this->billingInformation['enduser'] = $billingInformation['enduser'];
            }

            if (array_key_exists('description', $billingInformation)) {
                $this->billingInformation['description'] = $billingInformation['description'];
            }
        } else {
            $this->billingInformation = null;
        }

    }

    /**
     * @return string
     */
    public function getUniqueTransactionID()
    {
        return $this->uniqueTransactionID;
    }

    /**
     * @param string $uniqueTransactionID
     */
    public function setUniqueTransactionID($uniqueTransactionID)
    {
        $this->setBillingInformation(array('uniquetransactionid' => $uniqueTransactionID));
        $this->uniqueTransactionID = $uniqueTransactionID;
    }

    /**
     * @return float
     */
    public function getAmount()
    {
        return $this->amount;
    }

    /**
     * @param float $amount
     */
    public function setAmount($amount)
    {
        $this->setBillingInformation(array('amount' => $amount));
        $this->amount = $amount;
    }

    /**
     * @return string
     */
    public function getCurrency()
    {
        return $this->currency;
    }

    /**
     * @param string $currency
     */
    public function setCurrency($currency)
    {
        $this->setBillingInformation(array('currency' => $currency));
        $this->currency = $currency;
    }

    /**
     * @return array
     */
    public function getEnduser()
    {
        return $this->enduser;
    }

    /**
     * @param array $enduser
     */
    public function setEnduser($enduser)
    {
        if (array_key_exists('enduserid', $enduser)) {
            $this->enduser['enduserid'] = $enduser['enduserid'];
        }

        if (array_key_exists('endusertype', $enduser)) {
            $this->enduser['endusertype'] = $enduser['endusertype'];
        }

        if (array_key_exists('endusertoken', $enduser)) {
            $this->enduser['endusertoken'] = $enduser['endusertoken'];
        }
        $this->setBillingInformation(array('enduser' => $this->enduser));
//        $this->enduser = $enduser;
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @param string $description
     */
    public function setDescription($description)
    {
        $this->setBillingInformation(array('description' => $description));
        $this->description = $description;
    }

    public function callAPI($headers, $datas, $url, $method, $successCode, $jsonEncodeDatas = false, $timeout = 180)
    {
        $errors = array(
            'SYS001' => array(
                'type' => 'Systeme',
                'X-Retry-in' => 180,
                'libelle' => 'Le systeme est temporairement indisponible'
            ),
            'SYS999' => array(
                'type' => 'Systeme',
                'X-Retry-in' => -1,
                'libelle' => 'Erreur systeme non specifiee.'
            ),
            'DATA001' => array(
                'type' => 'Donnees',
                'X-Retry-in' => -1,
                'libelle' => 'Impossible de decoder le json fourni par le client.'
            ),
            'DATA002' => array(
                'type' => 'Donnees',
                'X-Retry-in' => -1,
                'libelle' => 'Une donnee imperative est absente. Le libelle de cette donnee est contenue dans le champ reasoncplt.'
            ),
            'ID001' => array(
                'type' => 'Identification',
                'X-Retry-in' => -1,
                'libelle' => 'Les champs username et password ne permettent pas d’identifier le client.'
            ),
            'ID002' => array(
                'type' => 'Identification',
                'X-Retry-in' => -1,
                'libelle' => 'La valeur du serviceid ne correspond pas à au compte de l’utilisateur authentifie.'
            ),
            'BILL001' => array(
                'type' => 'Billing',
                'X-Retry-in' => -1,
                'libelle' => 'Le montant à biller est superieur au seuil autorise pour ce type de transaction.'
            ),
            'BILL002' => array(
                'type' => 'Billing',
                'X-Retry-in' => 3600,
                'libelle' => 'Le seuil de facturation mensuel pour ce client sur ce serviceid est atteint.'
            ),
            'BILL003' => array(
                'type' => 'Billing',
                'X-Retry-in' => -1,
                'libelle' => 'La devise n’est pas autorisee pour ce serviceid.'
            ),
            'BILL004' => array(
                'type' => 'Billing',
                'X-Retry-in' => -1,
                'libelle' => 'Le titulaire de la ligne est blackliste et ne peut être facture.'
            ),
            'PADD001' => array(
                'type' => 'Paddock',
                'X-Retry-in' => '<variable>',
                'libelle' => 'Erreur generale du systeme Paddock. Le code d’erreur peut dans certains cas être retourne dans le champ reasoncplt.'
            )
        );

        $ch = curl_init();

        if (isset($headers) && !empty($headers)) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_HEADER, 0);
        }

        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_POST, true);

            if (!empty($datas)) {
                if ($jsonEncodeDatas === true)
                    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($datas));
                else
                    curl_setopt($ch, CURLOPT_POSTFIELDS, $datas);
            }
        }

        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 20);

        if ($this->isVerifyPeerSSL() === false)
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);

        $datas = curl_exec($ch);

        if ($datas === false)
            return array('error' => 'API call failed with cURL error: ' . curl_error($ch));

        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $returnHeaders = curl_getinfo($ch, CURLINFO_HEADER_OUT);

        curl_close($ch);

        $response = json_decode($datas, true);
        $jsonErrorCode = json_last_error();

        if ($jsonErrorCode !== JSON_ERROR_NONE) {

            $errorResponse = array(
                'error' => 'API response not well-formed (json error code: '
                    . $jsonErrorCode . ')'
            );
            $file = "error.txt";
            $datas = file_put_contents($file, json_encode($errorResponse));

            return $errorResponse;
        }

        if ($httpCode != $successCode) {
            $reason = $response['reason'];
            $reasoncplt = $response['reasoncplt'];
            $arrayReasonCplt = explode(" ", $reasoncplt);
            $reasoncpltMessage = $arrayReasonCplt[count($arrayReasonCplt) - 1];

            if ($reason == "PADD001")
                $reasoncpltMessage = "Credit insuffisant. veuillez recharger votre compte";

            $errorMessage = $reasoncpltMessage . PHP_EOL;

            $errorResponse = array('error' => $errorMessage);
            $file = "error.txt";
            $encoded = json_encode($errorResponse);
            $datas = file_put_contents($file, $encoded);
            return $errorResponse;
        }

        return $response;
    }

    /**
     * @return boolean
     */
    public function isVerifyPeerSSL()
    {
        return $this->verifyPeerSSL;
    }

    /**
     * @param boolean $verifyPeerSSL
     */
    public function setVerifyPeerSSL($verifyPeerSSL)
    {
        $this->verifyPeerSSL = $verifyPeerSSL;
    }

}