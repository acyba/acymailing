<input type="hidden" value="<?php echo acym_escape($splashJson); ?>" id="splashScreenJsonInfos" />

<div id="acym__splashscreen" class="cell grid-x ">
	<div class="acym__splashscreen__container">
		<div class="acym__splashscreen__box">
			<div class="acym__splashscreen__menu">
				<div class="acym__splashscreen__menu__title">
					<h2>
						<span class="acym__splashscreen__menu__title-big">NEW</span>
						<span class="acym__splashscreen__menu__title-updates">VERSION</span>
					</h2>

					<button class="acym_vcenter align-center large-shrink button"
							type="button" @click="skipButton"><?php echo acym_translation('ACYM_SKIP'); ?></button>

					</button>
				</div>

				<div v-for="menu in menus" :key="menu.menu_title" @click="toggleMenu(menu)">
					<h3 :class="{ acym__splashscreen__menu__active: activeMenu==menu }" class="acym__splashscreen__menu__container">
						<span class="acym__splashscreen__menu__span"> ></span> {{ menu.menu_title }}</h3>
				</div>
			</div>

			<div class="acym__splashscreen__body">
				<div v-if="activeMenu">
					<div class="acym__splashscreen__body__buttonWrapper">
						<a href="<?php echo ACYM_ACYMAILING_WEBSITE.'changelog/'; ?>"
						   class="acym_vcenter align-center large-shrink button"
						   target="_blank">
                            <?php echo acym_translation('ACYM_SEE_FULL_CHANGELOG'); ?>
						</a>
						<a v-if="this.menus.indexOf(this.activeMenu)+1 < this.menus.length"
						   class="acym_vcenter align-center large-shrink button button-secondary"
						   @click="toggleNextMenu"><?php echo acym_translation('ACYM_NEXT'); ?></a>
						<a v-if="this.menus.indexOf(this.activeMenu)+1 === this.menus.length"
						   class="acym_vcenter align-center large-shrink button button-secondary acym__splashscreen__bottom__skip__button"
						   @click="skipButton"><?php echo acym_translation('ACYM_SKIP'); ?></a>
					</div>

					<h2 class="acym__splashscreen__title">{{activeMenu.title}}</h2>

					<div v-for="article in activeMenu.articles" :key="article.title" class="acym__splashscreen__body__content">
						<h3>{{ article.title }}</h3>
						<p>{{ article.desc }}</p>
					</div>
					<button v-if="this.menus.indexOf(this.activeMenu)+1 === this.menus.length"
							class="acym_vcenter align-center large-shrink button acym__splashscreen__bottom__skip__button"
							type="button"
							@click="skipButton"><?php echo acym_translation('ACYM_SKIP'); ?></button>
				</div>


			</div>
		</div>
	</div>
</div>

