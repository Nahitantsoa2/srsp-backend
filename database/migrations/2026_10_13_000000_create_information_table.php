<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('information', function (Blueprint $table) {
            $table->id('id_information');
            $table->string('matricule', 30)->unique();
            $table->string('nom', 150);
            $table->string('prenom', 150)->nullable();
            $table->string('localite', 50);
            $table->timestamps();
        });

        // Reprise de la liste existante « Agents par localité » (27 agents)
        $agents = [
            // Localité 304
            ['509688', 'RANDRIANANDRASANA', 'Tsaravita D. C.', '304'],
            ['509689', 'RAKOTOMAROLAHY', 'Cyrille Jean Remi', '304'],
            ['509690', 'MIORANANTENAINA', 'Bakoliarisoa F.', '304'],
            ['509691', 'RASOANAMBININA', 'Géneviève Marie Sabine', '304'],
            ['509692', 'RAMANANDRAIBE', 'Marie Louise', '304'],
            ['509693', 'RANDRIANOELISOA', 'Mbolaniaina', '304'],
            ['509694', 'RAZAFINDRAFIDISOA', 'Rudmila Marie R.', '304'],
            ['509695', 'RAVAONIRINA', 'Danieline', '304'],
            // Localité 306
            ['509669', 'ANDRIAMAMINIAINATAFITASOA', 'D. N.', '306'],
            ['509670', 'HARINIVOMALALA', 'Thomas Elinà', '306'],
            ['509674', 'LAZA', 'FALINIARAVO Noëlson Simon', '306'],
            ['509677', 'RATSIMBAZAFIZANADRAY', 'Fleur R.', '306'],
            ['509678', 'RATOLOJANAHARY', 'Maminiaina Heritina', '306'],
            ['509679', 'RALIMANARIVO', 'Hasiniaina Irmah A.', '306'],
            ['509680', 'RASOA', 'Lalaina Marie Sophie', '306'],
            ['509681', 'RAVONIARISOA', 'Volatina Joséphine', '306'],
            ['509682', 'NIRINARIJAONA', 'Haingoniaina Henriette', '306'],
            ['509683', 'RASOLOFOMAMPIONONA', 'Jean Laurent', '306'],
            ['509684', 'ANDRIAMAMONJISOA', 'Jerinantenaina L.', '306'],
            ['509685', 'ANDRIAMANANTSOA', 'Tantely Elisé', '306'],
            ['509686', 'ANDRIATSIMIOVA', 'Moratafitasoa', '306'],
            ['509687', 'VONJIFANJANIRINA', 'FELANIAINA L. V.', '306'],
            // Localité 308
            ['509671', 'SOLOFOSON', 'Andriniaina Faniritsirahonana', '308'],
            ['509675', 'RAZAFILALAO', 'Marie Claudine', '308'],
            ['509676', 'ANDRIAMANANJARA', 'Tolojanahary T.', '308'],
            // Localité 324
            ['509672', 'RANDRIAMALAZAVOLA', 'Samüelson', '324'],
            ['509673', 'MALALANIRINA', 'Philbertine Marie P.', '324'],
        ];

        foreach ($agents as [$matricule, $nom, $prenom, $localite]) {
            DB::table('information')->insert([
                'matricule' => $matricule,
                'nom' => $nom,
                'prenom' => $prenom,
                'localite' => $localite,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('information');
    }
};
